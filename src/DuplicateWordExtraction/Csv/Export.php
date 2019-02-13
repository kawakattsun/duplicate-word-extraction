<?php

declare(strict_types=1);

namespace DuplicateWordExtraction\Csv;

use \DuplicateWordExtraction\App\Cli;
use \DuplicateWordExtraction\App\Config;
use \DuplicateWordExtraction\App\File;
use \DuplicateWordExtraction\App\Load;

class Export extends Load
{
    const PARAMETER_PATTERN = '/\d+(?:\.?\d+)?/';
    const KUTEN_PATTERN = '/(?<=[、。])/u';

    /**
     * csvから日本語が含まれるカラムを抽出し、
     * 翻訳対象リストとcsvの日本語文字列をlabelに変換したcsvを作成する。
     * - 日本語が含まれない場合はそのままコピー
     * - 日本語文字列内の数値はパラメータ化する.
     */
    public function execute(): void
    {
        Cli::info('Start export CSV.');
        foreach (glob(CSVDIR . '*.csv') as $path) {
            $file = new \SplFileObject($path);
            $file->setFlags(\SplFileObject::READ_CSV);
            $fileName = $file->getFilename();
            $outputCsvPath = OUTPUTCSVDIR . $fileName;
            File::createFile($outputCsvPath);
            $readLineCount = 0;
            $translateColumnCount = 0;
            Cli::info('Read csv: ' . $fileName);
            $columnNames = [];
            foreach ($file as $line => $row) {
                // 先頭行はカラム名のためそのままコピー
                if ($line === 0) {
                    $columnNames = $row;
                    File::writeCsv($outputCsvPath, $row);
                    continue;
                }
                $convertRow = [];
                foreach ($row as $index => $column) {
                    // 日本語文字列が含まれていなければそのままコピー
                    if ($this->includeJa((string) $column)) {
                        $convertRow[$index] = $column;
                        continue;
                    }
                    $this->before_strlen += mb_strlen($column, 'utf8');
                    // 文字列に数値が含まれる場合はparameter化しておく
                    $parameter = self::PARAMETER_DELIMITER;
                    if (Config::isPrameterColumn($fileName, $columnNames[$index]) &&
                        preg_match_all(self::PARAMETER_PATTERN, $column, $matches)
                    ) {
                        // 翻訳をデータを差し替える際にparameterはsprintfで挿入するため半角%をエスケープする
                        if (strpos($column, '%') !== false) {
                            $column = str_replace('%', '%%', $column);
                        }
                        $column = preg_replace(self::PARAMETER_PATTERN, '%s', $column);
                        $parameter .= implode(self::PARAMETER_DELIMITER, current($matches));
                        $parameter .= self::PARAMETER_DELIMITER;
                    }
                    $strLabel = '';
                    // 日本語文字列を翻訳リストに登録してlabel化したもので差し替え
                    // 句点分割対象の場合は分割して登録
                    if (Config::isSplitColumn($fileName, $columnNames[$index])) {
                        $splitColumn = preg_split(self::KUTEN_PATTERN, $column, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($splitColumn as $col) {
                            $strLabel .= $this->translateRegister($col);
                        }
                    } else {
                        [$str, $parameter] = $this->checkReturnAndSpace($column, $parameter);
                        $strLabel .= $this->translateRegister($str);
                    }
                    $convertRow[$index] = $strLabel . $parameter;
                    ++$translateColumnCount;
                }
                File::writeCsv($outputCsvPath, $convertRow);
                ++$readLineCount;
            }
            Cli::success('Read line count: ' . $readLineCount);
            Cli::success('Translate column count: ' . $translateColumnCount);

        }
        Cli::success('Before string length: ' . $this->before_strlen);
        Cli::success('After string length: ' . $this->after_strlen);
        Cli::info('End export CSV.');
    }
}
