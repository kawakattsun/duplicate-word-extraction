<?php
declare(strict_types=1);

namespace Csv;

class Export extends Load
{
    const PARAMETER_DELIMITER = ':';
    const LABEL_PREFIX = 'DATA_';
    const PARAMETER_PATTERN = '/\d+(?:\.?\d+)?/';

    private $translateFilePath = TRANSLATEDIR . 'translate.csv';
    private $translateData = [];
    private $translateLabelCounter = 0;

    /**
     * csvから日本語が含まれるカラムを抽出し、
     * 翻訳対象リストとcsvの日本語文字列をlabelに変換したcsvを作成する。
     * - 日本語が含まれない場合はそのままコピー
     * - 日本語文字列内の数値はパラメータ化する
     *
     * @return void
     */
    public function execute(): void
    {
        \App\Cli::info('Start export CSV.');
        $this->createCsvFile($this->translateFilePath);
        foreach (glob(CSVDIR . '*.csv') as $path) {
            $file = new \SplFileObject($path);
            $file->setFlags(\SplFileObject::READ_CSV);
            $fileName = $file->getFilename();
            $outputCsvPath = OUTPUTCSVDIR . $fileName;
            $this->createCsvFile($outputCsvPath);
            $first = true;
            $readLineCount = 0;
            $translateColumnCount = 0;
            \App\Cli::info('Read csv: ' . $fileName);
            foreach ($file as $line) {
                // 先頭行はカラム名のためそのままコピー
                if ($first) {
                    $this->writeCsv($outputCsvPath, $line);
                    $first = false;
                    continue;
                }
                $convertLine = [];
                foreach ($line as $index => $column) {
                    // 日本語文字列が含まれていなければそのままコピー
                    if (strlen($column) === mb_strlen($column, 'utf8')) {
                        $convertLine[$index] = $column;
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
     * 翻訳対象文言を登録する
     * 登録済みの場合はlabelを返却する
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