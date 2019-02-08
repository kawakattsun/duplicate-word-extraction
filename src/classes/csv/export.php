<?php

namespace Csv;

class Export
{
    const PARAMETER_PATTERN = '/\d+(?:\.?\d+)?/';
    const PARAMETER_DELIMITER = ':';
    const LABEL_PREFIX = 'DATA_';
    private $translateFilePath = TRANSLATEDIR . 'translate.csv';
    private $translateData = [];
    private $translateLabelCounter = 0;

    public function execute()
    {
        \App\Cli::info('Start export CSV.');
        $this->createCsvFile($this->translateFilePath);
        foreach (glob(CSVDIR . '*.csv') as $path) {
            $file = new \SplFileObject($path);
            $fileName = $file->getFilename();
            $outputCsvPath = OUTPUTCSVDIR . $fileName;
            $this->createCsvFile($outputCsvPath);
            $file->setFlags(\SplFileObject::READ_CSV);
            $first = true;
            $readLineCount = 0;
            $translateColumnCount = 0;
            \App\Cli::info('Read csv: ' . $fileName);
            foreach ($file as $line) {
                // 先頭行はカラム名
                if ($first) {
                    $this->writeCsv($outputCsvPath, $line);
                    $first = false;
                    continue;
                }
                $convertLine = [];
                foreach ($line as $index => $column) {
                    if (strlen($column) === mb_strlen($column, 'utf8')) {
                        $convertLine[$index] = $column;
                        continue;
                    }
                    $parameter = self::PARAMETER_DELIMITER;
                    if (preg_match_all(self::PARAMETER_PATTERN, $column, $matches)) {
                        if (strpos($column, '%') !== false) {
                            $column = str_replace('%', '%%', $column);
                        }
                        $column = preg_replace(self::PARAMETER_PATTERN, '%s', $column);
                        $parameter .= implode(self::PARAMETER_DELIMITER, current($matches));
                    }
                    $strLabel = $this->translateRegister($column);
                    $convertLine[$index] = $strLabel . $parameter;
                    ++$translateColumnCount;
                };
                $this->writeCsv($outputCsvPath, $convertLine);
                ++$readLineCount;
            }
            \App\Cli::success('Read line count: ' . $readLineCount);
            \App\Cli::success('Translate column count: ' . $translateColumnCount);
        }
        \App\Cli::info('End export CSV.');
    }

    /**
     * CSVファイルに書き出す
     *
     * @param $records
     */
    private function writeCsv($filePath, $records)
    {
        $res = fopen($filePath, 'a');
        fputcsv($res, $records);
        fclose($res);
    }

    /**
     * CSVファイルを作成する
     *
     * @return string
     */
    private function createCsvFile($filePath)
    {
        $res = fopen($filePath, 'w');
        if ($res === false) {
            \App\Cli::error('File create error. filePath: ' . $filePath);
            exit;
        }
        fclose($res);
    }

    private function translateRegister($str)
    {
        if (isset($this->translateData[$str])) {
            return $this->translateData[$str];
        }
        $strLabel = '[' . self::LABEL_PREFIX . sprintf('%06d', $this->translateLabelCounter) . ']';
        $this->translateData[$str] = $strLabel;
        ++$this->translateLabelCounter;
        $this->writeCsv($this->translateFilePath, [
            $strLabel,
            $str,
        ]);
        // \App\Cli::success('Registered word.');
        // \App\Cli::default($strLabel . ' ' . $str);

        return $strLabel;
    }
}