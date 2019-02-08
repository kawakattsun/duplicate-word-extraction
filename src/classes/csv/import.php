<?php

namespace Csv;

class Import
{
    const PARAMETER_DELIMITER = ':';
    const LABEL_PREFIX = 'DATA_';
    private $labelPrameterPattern = '/\[' . self::LABEL_PREFIX . '\d+\]:(?:\d+:?)*/';
    // private $labelPattern = '/\[' . self::LABEL_PREFIX . '\d+\]:/';
    private $translateFilePath = TRANSLATEDIR . 'import.csv';
    private $translateData = [];
    private $translateLabelCounter = 0;

    public function execute()
    {
        \App\Cli::info('Start import CSV.');
        $this->loadImportCsv();
        $searchLabelNeedle = '[' . self::LABEL_PREFIX;
        foreach (glob(OUTPUTCSVDIR . '*.csv') as $path) {
            $file = new \SplFileObject($path);
            $fileName = $file->getFilename();
            $importCsvPath = IMPORTCSVDIR . $fileName;
            $this->createCsvFile($importCsvPath);
            $file->setFlags(\SplFileObject::READ_CSV);
            $first = true;
            $readLineCount = 0;
            $translateColumnCount = 0;
            $notTralslateCount = 0;
            \App\Cli::info('Read csv: ' . $fileName);
            foreach ($file as $no => $line) {
                // 先頭行はカラム名
                if ($first) {
                    $this->writeCsv($importCsvPath, $line);
                    $first = false;
                    continue;
                }
                $convertLine = [];
                foreach ($line as $index => $column) {
                    if (strpos($column, $searchLabelNeedle) === false) {
                        $convertLine[$index] = $column;
                        continue;
                    }
                    preg_match($this->labelPrameterPattern, $column, $matches);
                    $labelAndParameters = explode(':', current($matches));
                    $label = array_shift($labelAndParameters);
                    if (empty($this->translateData[$label])) {
                        // \App\Cli::error('Not tralsrate label: ' . $label);
                        ++$notTralslateCount;
                        $convertLine[$index] = $column;
                        continue;
                    }
                    $parameters = empty(current($labelAndParameters)) ? '' : $labelAndParameters;
                    $str = preg_replace($this->labelPrameterPattern, $this->translateData[$label], $column);
                    $convertLine[$index] = !empty($parameters)
                        ? sprintf($str, ...$parameters)
                        : $str;
                    ++$translateColumnCount;
                };
                $this->writeCsv($importCsvPath, $convertLine);
                ++$readLineCount;
            }
            \App\Cli::success('Read line count: ' . $readLineCount);
            \App\Cli::success('Translate column count: ' . $translateColumnCount);
            \App\Cli::success('Not translate label count: ' . $notTralslateCount);
        }
        \App\Cli::info('End import CSV.');
    }

    private function loadImportCsv()
    {
        $file = new \SplFileObject($this->translateFilePath);
        $file->setFlags(\SplFileObject::READ_CSV);
        foreach ($file as $line) {
            if (empty($line[0])) {
                continue;
            }
            $after = empty($line[2]) ? '' : $line[2];
            $this->translateData[$line[0]] = $after;
        }
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
        $strLabel = '[DATA_' . sprintf('%06d', $this->translateLabelCounter) . ']';
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