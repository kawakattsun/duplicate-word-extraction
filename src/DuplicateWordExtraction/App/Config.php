<?php

declare(strict_types=1);

namespace DuplicateWordExtraction\App;

class Config
{
    private static $config;

    public static function load()
    {
        self::$config['splicColumns'] = [];
        self::$config['parameterColumns'] = [];
    }

    public static function isSplitColumn($table, $column): bool
    {
        self::load();
        return isset(self::$config['splicColumns'][$table][$column]);
    }

    public static function isPrameterColumn($table, $column): bool
    {
        self::load();
        // return isset(self::$config['parameterColumns'][$table][$column]);
        return true;
    }
}
