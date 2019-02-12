<?php

declare(strict_types=1);

namespace Csv;

use App\Cli;
use App\File;

class Export extends Load
{
    const PARAMETER_DELIMITER = ':';
    const LABEL_PREFIX = 'DATA_';
    const PARAMETER_PATTERN = '/\d+(?:\.?\d+)?/';

    private $translateData = [];

    /**
     * csvから日本語が含まれるカラムを抽出し、
     * 翻訳対象リストとcsvの日本語文字列をlabelに変換したcsvを作成する。
     * - 日本語が含まれない場合はそのままコピー
     * - 日本語文字列内の数値はパラメータ化する.
     */
    public function execute(): void
    {
        Cli::info('Start export CSV.');
        $this->translateData = File::loadTranslateFile();
        foreach (glob(CSVDIR . '*.csv') as $path) {
            $file = new \SplFileObject($path);
            $file->setFlags(\SplFileObject::READ_CSV);
            $fileName = $file->getFilename();
            $outputCsvPath = OUTPUTCSVDIR . $fileName;
            $this->createCsvFile($outputCsvPath);
            $readLineCount = 0;
            $translateColumnCount = 0;
            Cli::info('Read csv: ' . $fileName);
            foreach ($file as $line => $row) {
                // 先頭行はカラム名のためそのままコピー
                if ($line === 0) {
                    $this->writeCsv($outputCsvPath, $row);
                    continue;
                }
                $convertRow = [];
                foreach ($row as $index => $column) {
                    // 日本語文字列が含まれていなければそのままコピー
                    if (is_null($column) || strlen($column) === mb_strlen($column, 'utf8')) {
                        $convertRow[$index] = $column;
                        continue;
                    }
                    // 文字列に数値が含まれる場合はparameter化しておく
                    $parameter = self::PARAMETER_DELIMITER;
                    if (preg_match_all(self::PARAMETER_PATTERN, $column, $matches)) {
                        // 翻訳をデータを差し替える際にparameterはsprintfで挿入するため半角%をエスケープする
                        if (strpos($column, '%') !== false) {
                            $column = str_replace('%', '%%', $column);
                        }
                        $column = preg_replace(self::PARAMETER_PATTERN, '%s', $column);
                        $parameter .= implode(self::PARAMETER_DELIMITER, current($matches));
                    }
                    // 日本語文字列を翻訳リストに登録してlabel化したもので差し替え
                    $strLabel = $this->translateRegister($column);
                    $convertRow[$index] = $strLabel . $parameter;
                    ++$translateColumnCount;
                }
                $this->writeCsv($outputCsvPath, $convertRow);
                ++$readLineCount;
            }
            Cli::success('Read line count: ' . $readLineCount);
            Cli::success('Translate column count: ' . $translateColumnCount);
        }
        Cli::info('End export CSV.');
    }

    /**
     * 翻訳対象文言を登録する
     * 登録済みの場合はlabelを返却する.
     *
     * @param string $str
     *
     * @return string
     */
    private function translateRegister(string $str): string
    {
        if (isset($this->translateData[$str])) {
            return $this->translateData[$str];
        }
        if (empty($this->translateData)) {
            $translateLabelCounter = 0;
        } else {
            $translateLabelCounter = (int) preg_replace('/[^0-9]/', '', end($this->translateData)) + 1;
        }
        $strLabel = '[' . self::LABEL_PREFIX . sprintf('%06d', $translateLabelCounter) . ']';
        $this->translateData[$str] = $strLabel;
        File::writeTranslateFile([
            $strLabel,
            $str,
        ]);
        // Cli::success('Registered word.');
        // Cli::default($strLabel . ' ' . $str);

        return $strLabel;
    }
}
