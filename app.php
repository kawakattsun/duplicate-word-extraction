<?php
declare(strict_types=1);

define('DS', DIRECTORY_SEPARATOR);
define('HOMEPATH', __DIR__ . DS);
define('SRCPATH', HOMEPATH . 'src' . DS);
define('VENDORPATH', HOMEPATH . 'vendor' . DS);
require SRCPATH . 'bootstrap.php';

try {
    $app = bootstrap($argv);
    $app->run();
} catch (Exception $e) {
    App\Cli::error($e);
} catch (Throwable $e) {
    App\Cli::error($e);
}


