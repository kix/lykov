<?php
/**
 * Created by PhpStorm.
 * User: kix
 * Date: 02/10/15
 * Time: 16:38
 */

namespace Lykov\Command;


use Lykov\Slack\SocketClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    const TOKEN_KIX_PS = '';
    const TOKEN_LYKOV_TEST = '';

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run the Lykov bot')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Lykov bot started</info>');

        $loop = \React\EventLoop\Factory::create();
        $emitter = new \Evenement\EventEmitter();

        $pcntl = new \MKraemer\ReactPCNTL\PCNTL($loop);

        $pcntl->on(SIGTERM, function () use ($emitter) {
            $emitter->emit('terminate');
        });

        $pcntl->on(SIGTERM, function () use ($emitter) {
            $emitter->emit('terminate');
        });

        $emitter->on('error.fatal', function($msg) use ($loop, $output) {
            $loop->stop();
            $output->writeln('<fg="red">Fatal error occured: '.$msg.'</fg>');
        });

        $emitter->on('terminate', function() use ($loop, $emitter) {
            $emitter->emit('log', ['Terminating...', OutputInterface::VERBOSITY_NORMAL]);
            $loop->stop();
            die();
        });

        $emitter->on('log', function($msg, $verbosity = OutputInterface::VERBOSITY_DEBUG) use ($output) {
            if ($verbosity === OutputInterface::VERBOSITY_DEBUG) {
                $msg = 'DEBUG: '.$msg;
            }

            if ($output->getVerbosity() >= $verbosity) {
                $output->writeln($msg);
            }
        });

        $apiClient = new SocketClient($loop, $emitter, self::TOKEN_LYKOV_TEST);

        $emitter->emit('send', ['id' => 2, 'type' => 'message', 'channel' => 'D04F44TFL', 'text' => 'hello']);

        $loop->run();
    }
}