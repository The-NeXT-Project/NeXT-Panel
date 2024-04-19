<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\WebAuthnDevice;
use App\Services\WebAuthn;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

/**
 *  WebAuthnController
 */
final class WebAuthnController extends BaseController
{
    public function requestRegister(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson(WebAuthn::registerDevice($this->user));
    }

    public function registerHandler(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        return $response->withJson(WebAuthn::registerHandle($this->user, $this->antiXss->xss_clean($data)));
    }

    public function deleteDevice(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $webauthnDevice = (new WebAuthnDevice())->where('id', (int) $args['id'])->first();
        if ($webauthnDevice === null || $webauthnDevice->userid !== $this->user->id) {
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
}
