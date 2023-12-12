<?php

namespace App\Services;

use Illuminate\Support\Str;

class WebSocketService
{

    /**
     * 經過sha256 編碼auth token
     *
     * @param $params
     * @return array
     */
    public function auth($params): array
    {
        $signature = $params['socket_id'] . ':' . $params['channel_name'];
        return [
            "auth" => hash_hmac('sha256', $signature, env('PUSHER_APP_SECRET')),
        ];
    }

    /**
     * 檢驗回傳 auth token是否相符
     *
     * @param $payload
     * @param $connection
     * @return void
     */
    public function checkAuth($payload, $connection): void
    {
        if ($payload->data->auth?? false) {
            if (Str::after($payload->data->auth, ':') !== hash_hmac('sha256', "{$connection->socketId}:{$payload->data->channel}", $connection->app->secret)) {
                abort(401);
            }
        }
    }
}
