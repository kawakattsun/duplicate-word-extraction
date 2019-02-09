<?php
declare(strict_types=1);

namespace Csv;

class Load
{
    /**
     * Get Instance.
     *
     * @param string $type
     *
     * @return Load
     */
    public static function getInstance($type): Load
    {
        $class = 'Csv\\' . ucfirst($type);

        return new $class();
    }

    /**
     * CSVファイルに書き出す
     *
     * @param string $filePath
     * @param array $records
     *
     * @return void
     */
    protected function writeCsv(string $filePath, array $records): void
    {
        $res = fopen($filePath, 'a');
        fputcsv($res, $records);
        fclose($res);
    }

    /**
     * CSVファイルを作成する
     *
     * @param string $filePath
     *
     * @return void
     */
    protected function createCsvFile(string $filePath): void
    {
        $res = fopen($filePath, 'w');
        if ($res === false) {
            throw new \Exception('File create error. filePath: ' . $filePath);
        }
        fclose($res);
    }
}