<?php

declare(strict_types=1);

namespace App\Services\MFA;

use App\Models\MFACredential;
use App\Models\User;
use App\Services\Cache;
use App\Utils\Tools;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\RSA;
use Cose\Algorithms;
use Exception;
use Lcobucci\Clock\SystemClock;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

final class WebAuthn
{
    public static int $timeout = 30_000;

    public static function generateUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return PublicKeyCredentialUserEntity::create(
            $user->email,
            $user->uuid,
            $user->user_name
        );
    }

    public static function registerRequest(User $user): PublicKeyCredentialCreationOptions
    {
        $rpEntity = self::generateRPEntity();
        $userEntity = self::generateUserEntity($user);
        $authenticatorSelectionCriteria = AuthenticatorSelectionCriteria::create(
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
        );
        $publicKeyCredentialCreationOptions =
            PublicKeyCredentialCreationOptions::create(
                $rpEntity,
                $userEntity,
                random_bytes(32),
                pubKeyCredParams: self::getPublicKeyCredentialParametersList(),
                authenticatorSelection: $authenticatorSelectionCriteria,
                attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
                timeout: self::$timeout,
            );
        $redis = (new Cache())->initRedis();
        $redis->setex('webauthn_register:' . $user->id, 300, json_encode($publicKeyCredentialCreationOptions));
        return $publicKeyCredentialCreationOptions;
    }

    public static function generateRPEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create($_ENV['appName'], Tools::getSiteDomain());
    }

    public static function getPublicKeyCredentialParametersList(): array
    {
        return [
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_PS256),
            PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ED256),
        ];
    }

    public static function registerHandle(User $user, array $data): array
    {
        $serializer = self::getSerializer();

        try {
            $publicKeyCredential = $serializer->deserialize(
                json_encode($data),
                PublicKeyCredential::class,
                'json'
            );
        } catch (Exception $e) {
            return ['ret' => 0, 'msg' => $e->getMessage()];
        }
        if (! isset($publicKeyCredential->response) || ! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            return ['ret' => 0, 'msg' => '密钥类型错误'];
        }

        $redis = (new Cache())->initRedis();
        $publicKeyCredentialCreationOptions = $serializer->deserialize(
            $redis->get('webauthn_register:' . $user->id),
            PublicKeyCredentialCreationOptions::class,
            'json'
        );

        try {
            $authenticatorAttestationResponseValidator = self::getAuthenticatorAttestationResponseValidator();
            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
                $publicKeyCredential->response,
                $publicKeyCredentialCreationOptions,
                parse_url($_ENV['baseUrl'], PHP_URL_HOST)
            );
        } catch (Exception) {
            return ['ret' => 0, 'msg' => '验证失败'];
        }
        // save public key credential source
        $webauthn = new MFACredential();
        $webauthn->userid = $user->id;
        $webauthn->rawid = $publicKeyCredentialSource->jsonSerialize()['publicKeyCredentialId'];
        $webauthn->body = json_encode($publicKeyCredentialSource);
        $webauthn->created_at = date('Y-m-d H:i:s');
        $webauthn->used_at = null;
        $webauthn->name = $data['name'] === '' ?  null : $data['name'];
        $webauthn->type = 'passkey';
        $webauthn->save();
        return ['ret' => 1, 'msg' => '注册成功'];
    }

    public static function getSerializer(): SerializerInterface
    {
        $coseAlgorithmManager = Manager::create();
        $coseAlgorithmManager->add(ECDSA\ES256::create());
        $coseAlgorithmManager->add(RSA\RS256::create());
        $attestationStatementSupportManager = AttestationStatementSupportManager::create();
        $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
        $attestationStatementSupportManager->add(new PackedAttestationStatementSupport($coseAlgorithmManager));
        $attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport());
        $attestationStatementSupportManager->add(new TPMAttestationStatementSupport(SystemClock::fromSystemTimezone()));
        $attestationStatementSupportManager->add(new AppleAttestationStatementSupport());
        $attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport());
        $factory = new WebauthnSerializerFactory($attestationStatementSupportManager);
        return $factory->create();
    }

    public static function getAuthenticatorAttestationResponseValidator(): AuthenticatorAttestationResponseValidator
    {
        $csmFactory = new CeremonyStepManagerFactory();
        $creationCSM = $csmFactory->creationCeremony();
        return AuthenticatorAttestationResponseValidator::create(
            ceremonyStepManager: $creationCSM
        );
    }

    public static function challengeRequest(): PublicKeyCredentialRequestOptions
    {
        $publicKeyCredentialRequestOptions = self::getPublicKeyCredentialRequestOptions();
        $redis = (new Cache())->initRedis();

        $redis->setex('webauthn_assertion:' . session_id(), 300, json_encode($publicKeyCredentialRequestOptions));
        return $publicKeyCredentialRequestOptions;
    }

    public static function getPublicKeyCredentialRequestOptions(): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            rpId: Tools::getSiteDomain(),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: self::$timeout,
        );
    }

    public static function challengeHandle(array $data): array
    {
        $serializer = self::getSerializer();
        $publicKeyCredential = $serializer->deserialize(json_encode($data), PublicKeyCredential::class, 'json');
        if (! isset($publicKeyCredential->response) || ! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return ['ret' => 0, 'msg' => '验证失败'];
        }
        $publicKeyCredentialSource = (new MFACredential())
            ->where('rawid', $publicKeyCredential->id)
            ->where('type', 'passkey')
            ->first();
        if ($publicKeyCredentialSource === null) {
            return ['ret' => 0, 'msg' => '设备未注册'];
        }
        $user = (new User())->where('id', $publicKeyCredentialSource->userid)->first();
        if ($user === null) {
            return ['ret' => 0, 'msg' => '用户不存在'];
        }
        try {
            $redis = (new Cache())->initRedis();
            $publicKeyCredentialRequestOptions = $serializer->deserialize(
                $redis->get('webauthn_assertion:' . session_id()),
                PublicKeyCredentialRequestOptions::class,
                'json'
            );
            $authenticatorAssertionResponseValidator = self::getAuthenticatorAssertionResponseValidator();
            $publicKeyCredentialSource_body = $serializer->deserialize($publicKeyCredentialSource->body, PublicKeyCredentialSource::class, 'json');
            $result = $authenticatorAssertionResponseValidator->check(
                $publicKeyCredentialSource_body,
                $publicKeyCredential->response,
                $publicKeyCredentialRequestOptions,
                json_encode($data),
                $user->uuid,
                [Tools::getSiteDomain()]
            );
        } catch (Exception $e) {
            return ['ret' => 0, 'msg' => $e->getMessage()];
        }
        $publicKeyCredentialSource->body = json_encode($result);
        $publicKeyCredentialSource->used_at = date('Y-m-d H:i:s');
        $publicKeyCredentialSource->save();
        return ['ret' => 1, 'msg' => '验证成功', 'user' => $user];
    }

    public static function getAuthenticatorAssertionResponseValidator(): AuthenticatorAssertionResponseValidator
    {
        $csmFactory = new CeremonyStepManagerFactory();
        $requestCSM = $csmFactory->requestCeremony();
        return AuthenticatorAssertionResponseValidator::create(
            ceremonyStepManager: $requestCSM
        );
    }
}
