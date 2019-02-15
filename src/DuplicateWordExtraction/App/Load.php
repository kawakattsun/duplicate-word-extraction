<?php

declare(strict_types=1);

namespace DuplicateWordExtraction\App;

class Load
{
    const PARAMETER_DELIMITER = ':';
    const PARAMETER_RETURN = 'n';
    const PARAMETER_SPACE = 's';
    const LABEL_PREFIX = 'DATA_';

    protected $translateData = [];
    protected $translateLabelCounter = 0;
    protected $before_strlen = 0;
    protected $after_strlen = 0;

    /**
     * Get Instance.
     *
     * @param string $fileType
     * @param string $type
     *
     * @return Load
     */
    public static function getInstance(string $fileType, string $type): Load
    {
        $class = '\\DuplicateWordExtraction\\' . ucfirst($fileType) . '\\' . ucfirst($type);

        return new $class();
    }

    /**
     * Construct.
     *
     * @return void
     */
    private function __construct()
    {
        $this->initTranslateData();
    }

    /**
     * Init translate data.
     *
     * @return void
     */
    protected function initTranslateData(): void
    {
        $this->translateData = File::loadTranslateFile();
        if (!empty($this->translateData)) {
            $this->translateLabelCounter = (int) preg_replace('/[^0-9]/', '', end($this->translateData)) + 1;
        }
    }

    /**
     * 翻訳対象文言を登録する
     * 登録済みの場合はlabelを返却する
     *
     * @param string $str
     *
     * @return string
     */
    protected function translateRegister(string $str): string
    {
        if (isset($this->translateData[$str])) {
            return $this->translateData[$str];
        }
        $this->after_strlen += mb_strlen($str, 'utf8');
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

    /**
     * 日本語文字列が含まれているか
     *
     * @param string|null $str
     *
     * @return bool
     */
    protected function includeJa(?string $str): bool
    {
        return is_null($str) || strlen($str) === mb_strlen($str, 'utf8');
    }

    /**
     * 改行とスペースは削除して翻訳リストへ
     * 位置情報をパラメータ化
     *
     * @param string $column
     * @param string $parameter
     *
     * @return array
     */
    protected function checkReturnAndSpace(string $column, string $parameter = self::PARAMETER_DELIMITER): array
    {
        // $str = '';
        // $strArray = preg_split("//u", $column, -1, PREG_SPLIT_NO_EMPTY);
        // foreach ($strArray as $k => $v) {
        //     if ($v === PHP_EOL) {
        //         $parameter .= self::PARAMETER_RETURN . $k . self::PARAMETER_DELIMITER;
        //         continue;
        //     }
        //     if ($v === ' ') {
        //         $parameter .= self::PARAMETER_SPACE . $k . self::PARAMETER_DELIMITER;
        //         continue;
        //     }
        //     $str .= $v;
        // }
        $str = $column;

        return [$str, $parameter];
    }
}
