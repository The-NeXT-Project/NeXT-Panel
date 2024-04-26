<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\MFACredential;
use App\Services\MFA\FIDO;
use App\Services\MFA\TOTP;
use App\Services\MFA\WebAuthn;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 *  MFAController
 */
final class MFAController extends BaseController
{
    public function webauthnRequestRegister(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(WebAuthn::registerRequest($this->user));
    }

    public function webauthnRegisterHandler(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        return $response->withJson(WebAuthn::registerHandle($this->user, $this->antiXss->xss_clean($data)));
    }

    public function webauthnDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $webauthnDevice = (new MFACredential())->where('id', (int) $args['id'])
            ->where('type', 'passkey')
            ->where('userid', $this->user->id)->first();
        if ($webauthnDevice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '设备不存在',
            ]);
        }
        $webauthnDevice->delete();
        return $response->withHeader('HX-Refresh', true)->withJson([
            'ret' => 1,
            'msg' => '删除成功',
        ]);
    }

    public function totpRegisterRequest(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(TOTP::totpRegisterRequest($this->user));
    }

    public function totpRegisterHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $code = $request->getParam('code');

        if ($code === '' || $code === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '验证码不能为空',
            ]);
        }

        return $response->withJson(TOTP::totpRegisterHandle($this->user, $code));
    }

    public function totpVerifyHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $code = $request->getParam('code');

        if ($code === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => '二维码不能为空',
            ]);
        }
        return $response->withJson(TOTP::totpVerifyHandle($this->user, $code));
    }

    public function totpDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        (new MFACredential())->where('userid', $this->user->id)->where('type', 'totp')->delete();
        return $response->withHeader('HX-Refresh', true)->withJson([
            'ret' => 1,
            'msg' => '删除成功',
        ]);
    }

    public function fidoRegisterRequest(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(FIDO::fidoRegisterRequest($this->user));
    }

    public function fidoRegisterHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        return $response->withJson(FIDO::fidoRegisterHandle($this->user, $data));
    }

    public function fidoDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        (new MFACredential())->where('userid', $this->user->id)->where('type', 'fido')->where('id', (int) $args['id'])->delete();
        return $response->withHeader('HX-Refresh', true)->withJson([
            'ret' => 1,
            'msg' => '删除成功',
        ]);
    }
}
