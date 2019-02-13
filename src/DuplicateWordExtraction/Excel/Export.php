<?php
declare(strict_types=1);

namespace DuplicateWordExtraction\Excel;

use \DuplicateWordExtraction\App\Cli;
use \DuplicateWordExtraction\App\File;
use \DuplicateWordExtraction\App\Load;
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

    private $mode;

    /**
     * Execute.
     *
     * @return void
     */
    public function execute(): void
    {
        Cli::info('Start export Excel.');
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
        Cli::success('Before string length: ' . $this->before_strlen);
        Cli::success('After string length: ' . $this->after_strlen);
        Cli::info('End export Excel.');
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
                    if ($this->includeJa((string) $column)) {
                        continue;
                    }
                    $this->before_strlen += mb_strlen($column, 'utf8');
                    $this->executeColumn($sheet, $column, $index, $line);
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
            $character = !empty($row[self::QUEST_TEXT_COLUMN_CHARACTER_INDEX])
                ? $row[self::QUEST_TEXT_COLUMN_CHARACTER_INDEX]
                : '';
            $serif = !empty($row[self::QUEST_TEXT_COLUMN_SERIF_INDEX])
                ? $row[self::QUEST_TEXT_COLUMN_SERIF_INDEX]
                : '';
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
            if ($this->includeJa((string) $serif)) {
                continue;
            }
            $this->before_strlen += mb_strlen($serif, 'utf8');
            $this->executeColumn($sheet, $serif, self::QUEST_TEXT_COLUMN_SERIF_INDEX, $line);
            ++$translateColumnCount;
            ++$readLineCount;
        }
        Cli::success('Read line count: ' . $readLineCount);
        Cli::success('Translate column count: ' . $translateColumnCount);
    }

    /**
     * 日本語文字列を翻訳リストに登録してlabel化したもので差し替え
     *
     * @param Worksheet $sheet
     * @param string $column
     * @param int $index
     * @param int $line
     *
     * @return void
     */
    private function executeColumn(Worksheet $sheet, string $column, int $index, int $line): void
    {
        [$str, $parameter] = $this->checkReturnAndSpace($column);
        $strLabel = $this->translateRegister($str);
        $sheet->setCellValueByColumnAndRow(
            $index + 1,
            $line + 1,
            $strLabel . $parameter
        );
    }
}
