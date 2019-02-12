<?php
declare(strict_types=1);

namespace UnitTest\Csv;

use \PHPUnit\Framework\TestCase;
use \Csv\Load;

/**
 * @group csv
 */
class ExportTest extends TestCase
{
    public function testExport()
    {
        $export = Load::getInstance('export');
        $export->execute();
        $stack = [];
        $this->assertSame(0, count($stack));
    }
}