<?php

declare(strict_types=1);

namespace DuplicateWordExtraction\App;

class App
{
    private $argv;

    public function __construct($argv)
    {
        $this->argv = $argv;
    }

    public function run()
    {
        switch ($this->argv[1]) {
            case 'csv':
            case 'excel':
                $app = Load::getInstance($this->argv[1], $this->argv[2]);
                break;

            default:
                throw new \Exception('Not find argv.');
        }
        $app->execute();
    }
}
