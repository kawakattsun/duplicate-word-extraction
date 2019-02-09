<?php
declare(strict_types=1);

namespace Csv;

class Import extends Load
{
    const PARAMETER_DELIMITER = ':';
    const LABEL_PREFIX = 'DATA_';

    private $labelPrameterPattern = '/\[' . self::LABEL_PREFIX . '\d+\]:(?:\d+:?)*/';
    private $translateFilePath = TRANSLATEDIR . 'import.csv';
    private $translateData = [];
    private $translateLabelCounter = 0;

    /**
     * 翻訳リストからlabel変換したcsvへ文字列を挿入する
     *
     * @return void
     */
    public function execute(): void
    {
        \App\Cli::info('Start import CSV.');
        $this->loadImportCsv();
        $searchLabelNeedle = '[' . self::LABEL_PREFIX;
        foreach (glob(OUTPUTCSVDIR . '*.csv') as $path) {
            $file = new \SplFileObject($path);
            $file->setFlags(\SplFileObject::READ_CSV);
            $fileName = $file->getFilename();
            $importCsvPath = IMPORTCSVDIR . $fileName;
            $this->createCsvFile($importCsvPath);
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
                    // label化されていなければそのままコピー
                    if (is_null($column) || strpos($column, $searchLabelNeedle) === false) {
                        $convertLine[$index] = $column;
                        continue;
                    }
                    preg_match($this->labelPrameterPattern, $column, $matches);
                    $labelAndParameters = explode(':', current($matches));
                    $label = array_shift($labelAndParameters);
                    // label化されているが翻訳データが見つからない場合。labelのそのままコピー
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

    /**
     * Load Import Csv.
     *
     * @return void
     */
    private function loadImportCsv(): void
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
}