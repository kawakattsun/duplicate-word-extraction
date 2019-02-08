<?php

define('DS', DIRECTORY_SEPARATOR);
define('HOMEPATH', __DIR__ . DS);
define('SRCPATH', HOMEPATH . 'src' . DS);
define('VENDORPATH', HOMEPATH . 'vendor' . DS);
require SRCPATH . 'bootstrap.php';

$app = bootstrap($argv);
$app->run();

