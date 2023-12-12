<?php

namespace App\Http\Controllers;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessageFactory;
use BeyondCode\LaravelWebSockets\WebSockets\WebSocketHandler as WebSocket;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use App\Services\WebSocketService;

class WebsocketHandler extends WebSocket
{
    public WebSocketService $webSocketService;

    public function __construct(
        ChannelManager $channelManager,
        WebSocketService $webSocketService,
    ) {
        parent::__construct($channelManager);
        $this->webSocketService = $webSocketService;
    }

    public function onMessage(ConnectionInterface $connection, MessageInterface $message): void
    {
        $payload = json_decode($message->getPayload());
        $this->webSocketService->checkAuth($payload, $connection);

        $message = PusherMessageFactory::createForMessage($message, $connection, $this->channelManager);
        $message->respond();

        $broadcastChannels = [];

        if ($payload->event !='pusher:ping' && $payload->event !='pusher:pong') {
            $broadcastChannels = $this->channelManager->find($connection->app->id, $payload->data->channel);
        }

        if ($payload->event !='pusher_internal:subscription_succeeded' && $broadcastChannels) {
            foreach ($broadcastChannels->getSubscribedConnections() as $broadcastChannel) {
                $broadcastChannel->send(json_encode($payload));
            }
        }
    }

}
