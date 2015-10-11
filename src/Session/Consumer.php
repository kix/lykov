<?php
/**
 * Created by PhpStorm.
 * User: kix
 * Date: 02/10/15
 * Time: 17:22
 */

namespace Lykov\Session;


use Evenement\EventEmitterInterface;

class Consumer
{
    private $emitter;

    private $isStarted;

    private $dealer;

    private $players;

    private $results;

    private $estimating;

    public function __construct(EventEmitterInterface $emitter)
    {
        $this->emitter = $emitter;
        $this->emitter->on('session.start', [$this, 'onStart']);
        $this->emitter->on('session.stop', [$this, 'onStop']);
        $this->emitter->on('session.rate', [$this, 'onRate']);
        $this->emitter->on('session.estimate', [$this, 'onEstimateStart']);
        $this->emitter->on('session.status', [$this, 'onStatus']);
        $this->emitter->on('unknown', [$this, 'onUnknown']);
        $this->emitter->on('hello', [$this, 'onHello']);
    }

    public function onHello($msg)
    {
        $this->emitter->emit('send', [[
            'type' => 'message',
            'channel' => $msg->channel,
            'text' => 'Hello, this is Lykov bot speaking, powered by PHP '.PHP_VERSION,
        ]]);
    }

    public function onStatus($msg)
    {
        $status = [];

        if ($this->isStarted) {
            $status []= 'Session is started.';
            $status []= sprintf('Dealer is <@%s>', $this->dealer);
        } else {
            $status []= 'Session is not started.';
        }

        $status[]= sprintf('Currently estimating %s', ($this->estimating) ?: 'nothing');
        $status = array_merge($status, $this->formatResults());

        $this->emitter->emit('send', [[
            'type' => 'message',
            'channel' => $msg->channel,
            'text' => implode(PHP_EOL, $status),
        ]]);
    }

    public function onStart($msg)
    {
        if ($this->isStarted) {
            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => 'We have already started, haven\'t we?'
            ]]);

            return;
        }

        preg_match('/(\<@[A-Z0-9]*\>)/', $msg->text, $this->players);

        if (count($this->players) < 2) {
            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => 'You gotta have at least one other player. Playing alone is *rather* *boring* :neutral_face:.',
            ]]);

            return;
        }

        $this->dealer = $msg->user;
        $this->isStarted = true;

        $this->emitter->emit('send', [[
            'type' => 'message',
            'channel' => $msg->channel,
            'text' => sprintf(
                'Okay, let\'s do planning poker. <@%s> is the dealer, %s are players.',
                $this->dealer,
                implode(', ', $this->players)
            )
        ]]);

        $this->results = [];
    }

    public function onStop($msg)
    {
        if (!$this->isStarted) {
            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => 'To stop something, you gotta start it first. Say `@lykov start @user1 @user2` to start!'
            ]]);

            return;
        }

        $this->isStarted = false;

        $this->emitter->emit('send', [[
            'type' => 'message',
            'channel' => $msg->channel,
            'text' => sprintf(
                "Planning poker is over. <@%s> was the dealer. Here are the results:\n%s",
                $this->dealer,
                implode("\n", $this->formatResults())
            )
        ]]);
    }

    public function onEstimateStart($msg)
    {
        if ($this->estimating) {
            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => sprintf(
                    'Already estimating `%s`, finish it first!',
                    $this->estimating
                )
            ]]);
        } else {
            preg_match('/est\s+(.*)/', $msg->text, $matches);

            if (!array_key_exists(1, $matches)) {
                $this->emitter->emit('send', [[
                    'type' => 'message',
                    'channel' => $msg->channel,
                    'text' => 'Provide a title for the task, like so: `est eat a sandwich`',
                ]]);

                return;
            }

            $this->estimating = $matches[1];

            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => sprintf(
                    'Estimating %s',
                    $this->estimating
                )
            ]]);
        }
    }

    public function onRate($msg)
    {
        if (!$this->estimating) {
            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => 'Nothing to rate, start estimating something first!',
            ]]);

            return;
        }

        preg_match('/rate\s+(\d+)/', $msg->text, $matches);

        if (!count($matches)) {
            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => 'You need an estimation, like this: `est 3`',
            ]]);
        } elseif (count($matches) > 2) {
            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => 'Multiple rates? Pick one!',
            ]]);
        } else {
            $this->results[$this->estimating] = (int) $matches[1];

            $this->emitter->emit('send', [[
                'type' => 'message',
                'channel' => $msg->channel,
                'text' => 'Estimated as ' . $this->results[$this->estimating],
            ]]);
            $this->estimating = false;
        }
    }

    public function onUnknown($msg)
    {
        $this->emitter->emit('send', [[
            'type' => 'message',
            'channel' => $msg->channel,
            'text' => 'Hmm, what do you mean? I only speak `start`, `status`, `est`, `rate` and `stop`.',
        ]]);
    }

    private function formatResults()
    {
        $results = ['```'];
        foreach ($this->results as $k => $v) {
            $results []= "$k: $v";
        }
        $results []= '```';

        return $results;
    }
}