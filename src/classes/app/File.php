<?php

namespace App;

class File
{
    private static $translateFilePath = TRANSLATEDIR . 'translate.csv';

    /**
     * CSVファイルに書き出す.
     *
     * @param string $filePath
     * @param array  $records
     */
    public static function writeCsv(string $filePath, array $records): void
    {
        $res = fopen($filePath, 'a');
        fputcsv($res, $records);
        fclose($res);
    }

    /**
     * 空ファイルを作成する.
     *
     * @param string $filePath
     */
    public static function createFile(string $filePath): void
    {
        $res = fopen($filePath, 'w');
        if ($res === false) {
            throw new \Exception('File create error. filePath: ' . $filePath);
        }
        fclose($res);
    }

    /**
     * 翻訳データcsvをセットする.
     */
    private static function setTranslateFile(): void
    {
        if (file_exists(self::$translateFilePath)) {
            return;
        }
        self::createFile(self::$translateFilePath);
    }

    public static function loadTranslateFile(): array
    {
        self::setTranslateFile();
        $file = new \SplFileObject(self::$translateFilePath);
        $file->setFlags(\SplFileObject::READ_CSV);
        $translateData = [];
        foreach ($file as $line) {
            if (empty($line[0])) {
                continue;
            }
            $str = empty($line[2]) ? '' : $line[2];
            $translateData[$str] = $line[0];
        }

        return $translateData;
    }

    public static function writeTranslateFile(array $row): void
    {
        self::writeCsv(self::$translateFilePath, $row);
    }
}
