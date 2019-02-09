<?php
declare(strict_types=1);

namespace APP;

class App
{
    private $argv;

    public function __construct($argv) {
        $this->argv = $argv;
    }

    public function run()
    {
        switch ($this->argv[1]) {
            case 'csv':
                $app = \Csv\Load::getInstance($this->argv[2]);
                break;

            case 'excel':
                $app = \Excel\Load::getInstance($this->argv[2]);
                break;

            default:
                throw new \Exception('Not find argv.');
        };
        $app->execute();
    }
}