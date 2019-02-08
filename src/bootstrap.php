<?php

define('CSVDIR', HOMEPATH . 'storage' . DS . 'csv' . DS);
define('EXCELDIR', HOMEPATH . 'storage' . DS . 'excel' . DS);
define('OUTPUTDIR', HOMEPATH . 'storage' . DS . 'output' . DS);
define('OUTPUTCSVDIR', OUTPUTDIR . 'csv' . DS);
define('OUTPUTEXCELDIR', OUTPUTDIR . DS . 'excel' . DS);
define('TRANSLATEDIR', HOMEPATH . 'storage' . DS . 'translate' . DS);
define('IMPORTDIR', HOMEPATH . 'storage' . DS . 'import' . DS);
define('IMPORTCSVDIR', IMPORTDIR . 'csv' . DS);
define('IMPORTEXCELDIR', IMPORTDIR . 'excel' . DS);

require VENDORPATH . 'autoload.php';
require SRCPATH . 'autoload.php';

use App\App;
use Dotenv\Dotenv;

$dotenv = Dotenv::create(HOMEPATH);
$dotenv->load();


function bootstrap($argv)
{
    return new App($argv);
}