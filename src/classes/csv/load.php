<?php

namespace Csv;

class Load
{
    public static function getInstance($type)
    {
        $class = 'Csv\\' . ucfirst($type);
        return new $class();
    }
}