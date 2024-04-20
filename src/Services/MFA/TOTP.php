<?php

declare(strict_types=1);

namespace App\Services\MFA;

use App\Models\MFACredential;
use App\Models\User;
use App\Services\Cache;
use Exception;
use Vectorface\GoogleAuthenticator;

final class TOTP
{
    public static function totpRegisterRequest(User $user): array
    {
        try {
            if ((new MFACredential())->where('userid', $user->id)->where('type', 'totp')->exists()) {
                return ['ret' => 0, 'msg' => '您已经注册过TOTP'];
            }
            $ga = new GoogleAuthenticator();
            $token = $ga->createSecret(32);
            $redis = (new Cache())->initRedis();
            $redis->setex('totp_register:' . session_id(), 300, $token);
            return ['ret' => 1, 'msg' => '请求成功', 'url' => self::getGaUrl($user, $token), 'token' => $token];
        } catch (Exception $e) {
            return ['ret' => 0, 'msg' => $e->getMessage()];
        }
    }

    public static function totpRegisterHandle(User $user, string $code): array
    {
        $redis = (new Cache())->initRedis();
        $token = $redis->get('totp_register:' . session_id());
        if ($token === false) {
            return ['ret'=>0, 'msg'=>'验证码已过期，请刷新页面重试'];
        }
        $ga = new GoogleAuthenticator();
        if (!$ga->verifyCode($token, $code)) {
            return ['ret'=>0, 'msg'=>'验证码错误'];
        }
        $mfaCredential = new MFACredential();
        $mfaCredential->userid = $user->id;
        $mfaCredential->name = 'TOTP';
        $mfaCredential->body = json_encode(['token' => $token]);
        $mfaCredential->type = 'totp';
        $mfaCredential->created_at = date('Y-m-d H:i:s');
        $mfaCredential->save();
        $redis->del('totp_register:' . session_id());
        return ['ret'=>1, 'msg'=>'注册成功'];
    }

    public static function totpVerifyHandle(User $user, string $code): array
    {
        $ga = new GoogleAuthenticator();
        $mfaCredential = (new MFACredential)->where('userid', $user->id)->where('type', 'totp')->first();
        if ($mfaCredential === null) {
            return ['ret' => 0, 'msg' => '您还没有注册TOTP'];
        }
        $secret = json_decode($mfaCredential->body, true)['token'] ?? '';
        return $ga->verifyCode($secret, $code) ? ['ret' => 1, 'msg' => '验证成功'] : ['ret' => 0, 'msg' => '验证失败'];
    }

    public static function getGaUrl(User $user, string $token): string
    {
        return 'otpauth://totp/' .$_ENV['appName'].':'.rawurlencode($user->email).'?secret='.$token.'&issuer='.rawurlencode($_ENV['appName']);
    }
}
