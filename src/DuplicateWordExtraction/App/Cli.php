<?php

declare(strict_types=1);

namespace DuplicateWordExtraction\App;

class Cli
{
    const ANSI_ESCAPE = "\033[%sm%s\033[0m";
    const COLOR_RED = '0;31';
    const COLOR_GREEN = '0;32';
    const COLOR_YELLOW = '1;33';
    const COLOR_CYAN = '0;36';

    public static function default($str)
    {
        echo $str . PHP_EOL;
    }

    public static function info($str)
    {
        echo sprintf(self::ANSI_ESCAPE, self::COLOR_CYAN, $str) . PHP_EOL;
    }

    public static function warning($str)
    {
        echo sprintf(self::ANSI_ESCAPE, self::COLOR_YELLOW, $str) . PHP_EOL;
    }

    public static function error($str)
    {
        echo sprintf(self::ANSI_ESCAPE, self::COLOR_RED, $str) . PHP_EOL;
    }

    public static function success($str)
    {
        echo sprintf(self::ANSI_ESCAPE, self::COLOR_GREEN, $str) . PHP_EOL;
    }
}
