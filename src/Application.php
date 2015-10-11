<?php

namespace Lykov;

use Lykov\Command\RunCommand;
use Symfony\Component\Console\Application as ConsoleApplication;

class Application extends ConsoleApplication
{
    const VERSION = '0.1.0';

    public function __construct($name, $version)
    {
        parent::__construct($name, $version);

        $this->add(new RunCommand());
    }
}