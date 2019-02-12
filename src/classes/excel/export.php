<?php
declare(strict_types=1);

namespace Excel;

use \App\Cli;
use \App\File;
use \PhpOffice\PhpSpreadsheet;
use \PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Export extends Load
{
    const CHARACTER_TEXT = 'キャラクターテキスト';
    const QUEST_TEXT = 'クエストテキスト';
    const QUEST_TEXT_COLUMN_CHARACTER_INDEX = 0;
    const QUEST_TEXT_COLUMN_CHARACTER = '発言キャラ名';
    const QUEST_TEXT_COLUMN_SERIF_INDEX = 1;
    const QUEST_TEXT_COLUMN_SERIF = 'セリフ';
    const MODE_CHARACTER = 'character';
    const MODE_QUEST = 'quest';
    const PARAMETER_DELIMITER = ':';
    const LABEL_PREFIX = 'DATA_';

    private $mode;
    private $translateData = [];
    private $translateLabelCounter = 0;

    /**
     * Execute.
     *
     * @return void
     */
    public function execute(): void
    {
        Cli::info('Start export Excel.');
        $this->translateData = File::loadTranslateFile();
        if (!empty($this->translateData)) {
            $this->translateLabelCounter = (int) preg_replace('/[^0-9]/', '', end($this->translateData)) + 1;
        }
        foreach (glob(EXCELDIR . '*.xlsx') as $path) {
            $fileName = basename($path);
            if (strpos($fileName, self::CHARACTER_TEXT) !== false) {
                $this->mode = self::MODE_CHARACTER;
            }
            if (strpos($fileName, self::QUEST_TEXT) !== false) {
                $this->mode = self::MODE_QUEST;
            }
            if (is_null($this->mode)) {
                continue;
            }
            $outputExcelPath = OUTPUTEXCELDIR . $fileName;
            Cli::info('Read Excel: ' . $fileName);
            $worksheet = IOFactory::load($path);
            $this->tarnslateFacade($worksheet);
            $writer = new Xlsx($worksheet);
            $writer->save($outputExcelPath);
            $this->mode = null;
        }
    }

    private function tarnslateFacade(Spreadsheet $worksheet): void
    {
        $function = $this->mode . 'Translate';
        $this->$function($worksheet);
    }

    private function characterTranslate(Spreadsheet $worksheet): void
    {
        $spreadsheets = $worksheet->getAllSheets();
        foreach ($spreadsheets as $sheet) {
            $array = $sheet->toArray();
            $translateColumnCount = 0;
            $readLineCount = 0;
            Cli::info('Read sheet: ' . $sheet->getTitle());
            foreach ($array as $line => $row) {
                // 先頭２行は項目名
                if ($line <= 1) {
                    continue;
                }
                foreach ($row as $index => $column) {
                    // 日本語文字列が含まれていなければそのまま
                    if (empty($column) || strlen($column) === mb_strlen($column, 'utf8')) {
                        continue;
                    }
                    // 日本語文字列を翻訳リストに登録してlabel化したもので差し替え
                    // セリフ系はパラメータ化しないがcsvと処理の共通化のためdelimiterはつける
                    $splitColumn = preg_split('/(?<=[、。])/u', $column, -1, PREG_SPLIT_NO_EMPTY);
                    $strLabel = '';
                    foreach ($splitColumn as $col) {
                        $strLabel .= $this->translateRegister($col);
                    }
                    $sheet->setCellValueByColumnAndRow(
                        $index + 1,
                        $line + 1,
                        $strLabel . self::PARAMETER_DELIMITER
                    );
                    ++$translateColumnCount;
                }
                ++$readLineCount;
            }
            Cli::success('Read line count: ' . $readLineCount);
            Cli::success('Translate column count: ' . $translateColumnCount);
        }
    }

    private function questTranslate(Spreadsheet $worksheet): void
    {
        $sheet = $worksheet->getActiveSheet();
        $array = $sheet->toArray();
        $translateColumnCount = 0;
        $readLineCount = 0;
        Cli::info('Read sheet: ' . $sheet->getTitle());
        $isTranslateRow = false;
        foreach ($array as $line => $row) {
            $character = !empty($row[self::QUEST_TEXT_COLUMN_CHARACTER_INDEX]) ? $row[self::QUEST_TEXT_COLUMN_CHARACTER_INDEX] : '';
            $serif = !empty($row[self::QUEST_TEXT_COLUMN_SERIF_INDEX]) ? $row[self::QUEST_TEXT_COLUMN_SERIF_INDEX] : '';
            if (strpos($character, self::QUEST_TEXT_COLUMN_CHARACTER) !== false &&
                strpos($serif, self::QUEST_TEXT_COLUMN_SERIF) !== false
            ) {
                $isTranslateRow = true;
                continue;
            } elseif (!$isTranslateRow) {
                continue;
            } elseif (empty($character)) {
                $isTranslateRow = false;
                continue;
            }
            // 日本語文字列が含まれていなければそのまま
            if (strlen($serif) === mb_strlen($serif, 'utf8')) {
                continue;
            }
            // 日本語文字列を翻訳リストに登録してlabel化したもので差し替え
            // セリフ系はパラメータ化しないがcsvと処理の共通化のためdelimiterはつける
            $splitSerif = preg_split('/(?<=[、。])/u', $serif, -1, PREG_SPLIT_NO_EMPTY);
            $strLabel = '';
            foreach ($splitSerif as $str) {
                $strLabel .= $this->translateRegister($str);
            }
            $sheet->setCellValueByColumnAndRow(
                self::QUEST_TEXT_COLUMN_SERIF_INDEX + 1,
                $line + 1,
                $strLabel . self::PARAMETER_DELIMITER
            );
            ++$translateColumnCount;
            ++$readLineCount;
        }
        Cli::success('Read line count: ' . $readLineCount);
        Cli::success('Translate column count: ' . $translateColumnCount);
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
        File::writeTranslateFile([
            $strLabel,
            $str,
        ]);
        ++$this->translateLabelCounter;
        // Cli::success('Registered word.');
        // Cli::default($strLabel . ' ' . $str);

        return $strLabel;
    }
}