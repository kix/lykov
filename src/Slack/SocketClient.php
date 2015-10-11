<?php

namespace Lykov\Slack;

use Evenement\EventEmitterInterface;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;
use Lykov\Session\Consumer;
use Ratchet\Client\Factory as ClientFactory;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SocketClient
{
    private $selfId;

    private $emitter;

    private $msgCounter = 0;

    public function __construct(LoopInterface $loop, EventEmitterInterface $emitter, $token)
    {
        $self = $this;
        $this->emitter = $emitter;
        $connector = new ClientFactory($loop);

        $endpoint = $this->getEndpoint($token);
        $consumer = new Consumer($emitter);

        $connector($endpoint)->then(function(WebSocket $conn) use ($self, $emitter) {
            $emitter->emit('log', ['Connected!']);

            $conn->on('message', function ($msg) use ($self, $emitter) {
                $emitter->emit('log', ['Received a message: '.$msg, OutputInterface::VERBOSITY_DEBUG]);

                $msgData = json_decode($msg);
                if (property_exists($msgData, 'user') && $msgData->user === $self->selfId) {
                    return;
                }

                if (property_exists($msgData, 'channel') && substr($msgData->channel, 0, 1) === 'D' && property_exists($msgData, 'text')) {
                    if (strpos($msgData->text, 'start') !== false) {
                        $self->log('Received a start request: ' . json_encode($msg));
                        $emitter->emit('session.start', [$msgData]);
                    }

                    elseif (strpos($msgData->text, 'stop') !== false) {
                        $self->log('Received a stop request: ' . json_encode($msg));
                        $emitter->emit('session.stop', [$msgData]);
                    }

                    elseif (strpos($msgData->text, 'est') !== false) {
                        $self->log('Received an estimate request: ' . json_encode($msg));
                        $emitter->emit('session.estimate', [$msgData]);
                    }

                    elseif (strpos($msgData->text, 'rate') !== false) {
                        $emitter->emit('session.rate', [$msgData]);
                    }

                    elseif (strpos($msgData->text, 'status') !== false) {
                        $self->log('Received an estimate request: ' . json_encode($msg));
                        $emitter->emit('session.status', [$msgData]);
                    }

                    elseif (strpos($msgData->text, 'hello') !== false) {
                        $emitter->emit('hello', [$msgData]);
                    }

                    else {
                        $emitter->emit('unknown', [$msgData]);
                    }
                }
            });

            // $emitter->on('send', function($msg) use ($conn, $self) {
            //     $self->increase();
            //     $msg['id'] = $self->msgCounter;
            //     $self->log('Sending message: '.json_encode($msg));
            //     $conn->send(json_encode($msg));
            //     $self->increase();
            // });

        }, function($e) use ($loop, $emitter) {
            $emitter->emit('error.fatal', "Could not connect: {$e->getMessage()}\n");
            $loop->stop();
        });
    }

    private function increase()
    {
        ++$this->msgCounter;
    }

    private function log($message, $verbosity = OutputInterface::VERBOSITY_DEBUG)
    {
        $this->emitter->emit('log', [$message, $verbosity]);
    }

    private function getEndpoint($token)
    {
        $client = new Client();

        $request = new Request(
            'POST',
            'https://slack.com/api/rtm.start?' . http_build_query(['token' => $token]),
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );

        $this->emitter->emit('log', ['Requesting RTM start']);
        $response = $client->send($request);
        $responseData = json_decode($response->getBody(true));

        $this->emitter->emit('log', ['RTM started']);
        $this->selfId = $responseData->self->id;
        $this->emitter->emit('log', ['My ID is: '.$this->selfId]);

        return $responseData->url;
    }
}