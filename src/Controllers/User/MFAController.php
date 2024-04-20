<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\MFACredential;
use App\Services\MFA;
use App\Services\MFA\WebAuthn;
use Exception;
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
        $data = (array)$request->getParsedBody();
        return $response->withJson(WebAuthn::registerHandle($this->user, $this->antiXss->xss_clean($data)));
    }

    public function webauthnDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $webauthnDevice = (new MFACredential())->where('id', (int)$args['id'])
            ->where('type', 'passkey')
            ->where('userid', $this->user->id)->first();
        if ($webauthnDevice === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '设备不存在',
            ]);
        }
        $webauthnDevice->delete();
        return $response->withHeader('HX-Redirect', '/user/edit#login_security')->withJson([
            'ret' => 1,
            'msg' => '删除成功',
        ]);
    }

    public function totpRegisterRequest(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(MFA\TOTP::totpRegisterRequest($this->user));
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

        return $response->withJson(MFA\TOTP::totpRegisterHandle($this->user, $code));
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
        return $response->withJson(MFA\TOTP::totpVerifyHandle($this->user, $code));
    }

    public function totpDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        (new MFACredential())->where('userid', $user->id)->where('type', 'totp')->delete();
        return $response->withJson([
            'ret' => 1,
            'msg' => '删除成功',
        ]);
    }

    public function fidoRegisterRequest(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(MFA\FIDO::fidoRegisterRequest($this->user));
    }

    public function fidoRegisterHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        return $response->withJson(MFA\FIDO::fidoRegisterHandle($this->user, $data));
    }

    public function fidoDelete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $id = $request->getParam('id');
        if ($id === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'ID不能为空',
            ]);
        }
        $device = (new MFACredential())->where('userid', $user->id)->where('type', 'fido')->where('id', $id)->first();
        (new MFACredential())->where('userid', $user->id)->where('type', 'fido')->delete();
        return $response->withJson([
            'ret' => 1,
            'msg' => '删除成功',
        ]);
    }

    public function setGa(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $enable = $request->getParam('enable');

        if ($enable === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => '选项无效',
            ]);
        }

        $user = $this->user;
        $user->ga_enable = $enable;
        $user->save();

        if ($user->save()) {
            return $response->withJson([
                'ret' => 1,
                'msg' => '设置成功',
            ]);
        }

        return $response->withJson([
            'ret' => 0,
            'msg' => '设置失败',
        ]);
    }

    /**
     * @throws Exception
     */
    public function resetGa(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $user->ga_token = MFA::generateGaToken();

        if ($user->save()) {
            return $response->withJson([
                'ret' => 1,
                'msg' => '重置成功',
                'data' => [
                    'ga-token' => $user->ga_token,
                    'ga-url' => MFA::getGaUrl($user),
                ],
            ]);
        }

        return $response->withJson([
            'ret' => 0,
            'msg' => '重置失败',
        ]);
    }
}
