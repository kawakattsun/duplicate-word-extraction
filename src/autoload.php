<?php

spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', '/', strtolower($class));
    require SRCPATH . 'classes' . DS . $classPath . '.php';
});
