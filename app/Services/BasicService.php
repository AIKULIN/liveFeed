<?php

namespace App\Services;

class BasicService
{

    public function dUid($encrypted)
    {
        $iv = env("ENCRYPTED_IV");//
        $decrypt_key = env("ENCRYPTED_KEY");
        $decrypted = openssl_decrypt($encrypted, 'AES-128-CBC',$decrypt_key,OPENSSL_ZERO_PADDING, $iv);

        $decrypted = preg_replace('/\\x00/', '', $decrypted);
        return json_decode($decrypted, true);
    }

    /**
     * 自制簡易 TOKEN 驗證
     * 仿製 JWT
     * @param $authorization
     * @return false
     */
    public function checkAuthorization($authorization): bool
    {
        $authorization = str_replace('Bearer ', '', $authorization);
        $deCode = $this->dUid($authorization);

        if (env('APP_DEBUG')) {
            return false;
        }

        if (is_null($deCode)) {
            abort(401);
        }

        // 確認TOKEN 時間是否過期
        if ($this->checkTime($deCode['iat'], $deCode['exp'])) {
            abort(401);
        }

        if ($this->deCodeJti($deCode['jti'])) {
            abort(401);
        }

        return false;
    }

    /**
     * 確認產生TOKEN 時間是否過期
     *
     * @param $iat
     * @param $exp
     * @return bool
     */
    private function checkTime($iat, $exp) : bool
    {
        $iat = (int) substr($iat, 0, -3);
        $exp = (int) substr($exp, 0, -3);
        $now = time();
        if ($now >= $iat && $now <= $exp) {
            return false;
        }

        return true;
    }

    /**
     * 確認檢驗碼是否正確 非唯一值
     *
     * @param $enCode
     * @return bool
     */
    private function deCodeJti($enCode) : bool
    {
        $deCode = $this->dUid($enCode);
        if ($deCode['source'] === env('SOURCE_JTI') && $deCode['encrypt_key'] === env('ENCRYPTED_KEY')) {
            return false;
        }

        return true;
    }
}
