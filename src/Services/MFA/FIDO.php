<?php

declare(strict_types=1);

namespace App\Services\MFA;

use App\Models\MFACredential;
use App\Models\User;
use App\Services\Cache;
use App\Utils\Tools;
use Exception;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

final class FIDO
{
    public static function fidoRegisterRequest(User $user): PublicKeyCredentialCreationOptions
    {
        $rpEntity = WebAuthn::generateRPEntity();
        $userEntity = WebAuthn::generateUserEntity($user);
        $authenticatorSelectionCriteria = AuthenticatorSelectionCriteria::create();
        $publicKeyCredentialCreationOptions =
            PublicKeyCredentialCreationOptions::create(
                $rpEntity,
                $userEntity,
                random_bytes(32),
                pubKeyCredParams: WebAuthn::getPublicKeyCredentialParametersList(),
                authenticatorSelection: $authenticatorSelectionCriteria,
                attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
                timeout: WebAuthn::$timeout,
            );
        $redis = (new Cache())->initRedis();
        $redis->setex('fido_register:' . session_id(), 300, json_encode($publicKeyCredentialCreationOptions));
        return $publicKeyCredentialCreationOptions;
    }

    public static function fidoRegisterHandle(User $user, array $data): array
    {
        $serializer = WebAuthn::getSerializer();

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
            $redis->get('fido_register:' . session_id()),
            PublicKeyCredentialCreationOptions::class,
            'json'
        );

        try {
            $authenticatorAttestationResponseValidator = WebAuthn::getAuthenticatorAttestationResponseValidator();
            $publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
                $publicKeyCredential->response,
                $publicKeyCredentialCreationOptions,
                parse_url($_ENV['baseUrl'], PHP_URL_HOST)
            );
        } catch (Exception) {
            return ['ret' => 0, 'msg' => '验证失败'];
        }
        $mfaCredential = new MFACredential();
        $mfaCredential->userid = $user->id;
        $mfaCredential->rawid = $publicKeyCredentialSource->jsonSerialize()['publicKeyCredentialId'];
        $mfaCredential->body = json_encode($publicKeyCredentialSource);
        $mfaCredential->created_at = date('Y-m-d H:i:s');
        $mfaCredential->used_at = null;
        $mfaCredential->name = $data['name'] === '' ? null : $data['name'];
        $mfaCredential->type = 'fido';
        $mfaCredential->save();
        return ['ret' => 1, 'msg' => '注册成功'];
    }

    public static function fidoAssertRequest(User $user): PublicKeyCredentialRequestOptions
    {
        $serializer = WebAuthn::getSerializer();
        $userCredentials = (new MFACredential())->where('userid', $user->id)->where('type', 'fido')->get(['body']);
        $credentials = [];
        foreach ($userCredentials as $credential) {
            $credentials[] = $serializer->deserialize($credential->body, PublicKeyCredentialSource::class, 'json');
        }
        $allowedCredentials = array_map(
            static function (PublicKeyCredentialSource $credential): PublicKeyCredentialDescriptor {
                return $credential->getPublicKeyCredentialDescriptor();
            },
            $credentials
        );
        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            rpId: Tools::getSiteDomain(),
            allowCredentials: $allowedCredentials,
            userVerification: 'discouraged',
            timeout: WebAuthn::$timeout,
        );
        $redis = (new Cache())->initRedis();
        $redis->setex('fido_assertion:' . session_id(), 300, json_encode($publicKeyCredentialRequestOptions));
        return $publicKeyCredentialRequestOptions;
    }

    public static function fidoAssertHandle(User $user, array $data): array
    {
        $serializer = WebAuthn::getSerializer();
        $publicKeyCredential = $serializer->deserialize(json_encode($data), PublicKeyCredential::class, 'json');
        if (! isset($publicKeyCredential->response) || ! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return ['ret' => 0, 'msg' => '验证失败'];
        }
        $publicKeyCredentialSource = (new MFACredential())->where('rawid', $publicKeyCredential->id)->first();
        if ($publicKeyCredentialSource === null || $user->id !== $publicKeyCredentialSource->userid) {
            return ['ret' => 0, 'msg' => '设备未注册'];
        }
        try {
            $redis = (new Cache())->initRedis();
            $publicKeyCredentialRequestOptions = $serializer->deserialize(
                $redis->get('fido_assertion:' . session_id()),
                PublicKeyCredentialRequestOptions::class,
                'json'
            );
            $authenticatorAssertionResponseValidator = WebAuthn::getAuthenticatorAssertionResponseValidator();
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
            return ['ret' => 0, 'msg' => '111' . $e->getMessage()];
        }
        $publicKeyCredentialSource->body = json_encode($result);
        $publicKeyCredentialSource->used_at = date('Y-m-d H:i:s');
        $publicKeyCredentialSource->save();
        return ['ret' => 1, 'msg' => '验证成功', 'userid' => $user->id];
    }
}
