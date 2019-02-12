<?php
declare(strict_types=1);

define('DS', DIRECTORY_SEPARATOR);
define('HOMEPATH', __DIR__ . DS);
define('SRCPATH', HOMEPATH . 'src' . DS);
define('VENDORPATH', HOMEPATH . 'vendor' . DS);
require SRCPATH . 'bootstrap.php';

use App\Cli;

try {
    $app = bootstrap($argv);
    $app->run();
} catch (Exception $e) {
    Cli::error($e);
} catch (Throwable $e) {
    Cli::error($e);
}
