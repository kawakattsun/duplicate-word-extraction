<?php
declare(strict_types=1);

define('DS', DIRECTORY_SEPARATOR);
define('HOMEPATH', realpath(__DIR__ .'/../') . DS);
define('SRCPATH', HOMEPATH . 'src' . DS);
define('VENDORPATH', HOMEPATH . 'vendor' . DS);

define('CSVDIR', realpath(HOMEPATH . 'tests/storage/csv/') . DS);
define('EXCELDIR', realpath(HOMEPATH . 'tests/storage/excel/') . DS);
define('OUTPUTDIR', realpath(HOMEPATH . 'tests/storage/output/') . DS);
define('OUTPUTCSVDIR', OUTPUTDIR . 'csv' . DS);
define('OUTPUTEXCELDIR', OUTPUTDIR . 'excel' . DS);
define('TRANSLATEDIR', realpath(HOMEPATH . 'tests/storage/translate/') . DS);
define('IMPORTDIR', realpath(HOMEPATH . 'tests/storage/import/') . DS);
define('IMPORTCSVDIR', IMPORTDIR . 'csv' . DS);
define('IMPORTEXCELDIR', IMPORTDIR . 'excel' . DS);

require VENDORPATH . 'autoload.php';
