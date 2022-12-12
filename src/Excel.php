<?php

/**
 * Copyright (C) 2010 LINEAR JAPAN Co., Ltd. All Rights Reserved.
 *
 * This source code or any portion thereof must not be
 * reproduced or used in any manner whatsoever.
 */

namespace Blocs;

/**
 * Package linear excel.
 *
 * テンプレートとなるエクセルファイルをとり込んで値の編集ができる
 * グラフや計算処理はエクセルファイルで実行する前提
 */
class Excel
{
    private $excelName;
    private $excelTemplate;

    private $worksheet = [];
    private $worksheetXml = [];
    private $addShared = false;

    /**
     * コンストラクタ
     *
     * テンプレートとなるエクセルファイルの取り込み。
     *
     * @param string $excelName テンプレートファイル名
     */
    public function __construct($excelName)
    {
        defined('TEMPLATE_CACHE_DIR') || define('TEMPLATE_CACHE_DIR', config('view.compiled'));

        $this->excelName = $excelName;
        $this->excelTemplate = new \ZipArchive();
        $this->excelTemplate->open($excelName);
    }

    /**
     * テンプレートとなるエクセルファイルの値を編集.
     *
     * @param string $sheetNo     シートの番号、左から1,2とカウント
     * @param string $sheetColumn 編集するカラムの列番号、もしくは列名
     * @param string $sheetRow    編集するカラムの行番号、もしくは行名
     */
    public function get($sheetNo, $sheetColumn, $sheetRow)
    {
        $sheetName = 'xl/worksheets/sheet'.$sheetNo.'.xml';

        if (isset($this->worksheetXml[$sheetName])) {
            // キャッシュを読み込み
            $worksheetXml = $this->worksheetXml[$sheetName];
        } else {
            $worksheetString = $this->_get_worksheet($sheetName);

            // シートがない時
            if (empty($worksheetString)) {
                return false;
            }

            $worksheetXml = new \SimpleXMLElement($worksheetString);

            // キャッシュを作成
            $this->worksheetXml[$sheetName] = $worksheetXml;
        }

        // 列番号、行番号を列名、行名に変換
        list($columnName, $rowName) = $this->_get_name($sheetColumn, $sheetRow);

        // 指定されたセルの値を取得
        $value = $this->_get_value_sheet($worksheetXml, $columnName, $rowName);

        return $value;
    }

    /**
     * テンプレートとなるエクセルファイルの値を編集.
     *
     * @param string $sheetNo     シートの番号、左から1,2とカウント
     * @param string $sheetColumn 編集するカラムの列番号、もしくは列名
     * @param string $sheetRow    編集するカラムの行番号、もしくは行名
     * @param string $value       値
     */
    public function set($sheetNo, $sheetColumn, $sheetRow, $value)
    {
        $sheetName = 'xl/worksheets/sheet'.$sheetNo.'.xml';

        if (isset($this->worksheetXml[$sheetName])) {
            // キャッシュを読み込み
            $worksheetXml = $this->worksheetXml[$sheetName];
        } else {
            $worksheetString = $this->_get_worksheet($sheetName);

            // シートがない時
            if (empty($worksheetString)) {
                return $this;
            }

            $worksheetXml = new \SimpleXMLElement($worksheetString);
        }

        // 列番号、行番号を列名、行名に変換
        list($columnName, $rowName) = $this->_get_name($sheetColumn, $sheetRow);

        // 指定されたセルに値をセット
        $worksheetXml = $this->_set_value_sheet($worksheetXml, $columnName, $rowName, $value);

        $this->worksheet[$sheetName] = $worksheetXml->asXML();

        // キャッシュを更新
        $this->worksheetXml[$sheetName] = $worksheetXml;

        return $this;
    }

    /**
     * エクセルファイルの生成.
     */
    public function generate()
    {
        $excelTemplate = $this->excelTemplate;
        $worksheet = $this->worksheet;

        $tempName = tempnam(TEMPLATE_CACHE_DIR, 'excel');
        $generateName = $tempName.'.zip';
        $excelGenerate = new \ZipArchive();
        $excelGenerate->open($generateName, \ZipArchive::CREATE);

        for ($i = 0; $i < $excelTemplate->numFiles; ++$i) {
            $sheetName = $excelTemplate->getNameIndex($i);
            $worksheetString = $excelTemplate->getFromIndex($i);

            if ('xl/workbook.xml' == $sheetName) {
                $worksheetXml = new \SimpleXMLElement($worksheetString);

                if (isset($worksheetXml->calcPr['forceFullCalc'])) {
                    // テンプレートそのままのシート
                    $excelGenerate->addFromString($sheetName, $worksheetString);
                } else {
                    // 強制的に計算させる
                    $worksheetXml->calcPr->addAttribute('forceFullCalc', 1);

                    $excelGenerate->addFromString($sheetName, $worksheetXml->asXML());
                }

                continue;
            }

            if (isset($worksheet[$sheetName])) {
                // 値を差し替えたシート
                $excelGenerate->addFromString($sheetName, $worksheet[$sheetName]);
                continue;
            }

            // テンプレートそのままのシート
            $excelGenerate->addFromString($sheetName, $worksheetString);
        }

        if ($this->addShared) {
            // 共通文字列のシートを追加
            $excelGenerate->addFromString('xl/sharedStrings.xml', $worksheet['xl/sharedStrings.xml']);
        }

        $excelTemplate->close();
        $excelGenerate->close();

        $excelGenerated = file_get_contents($generateName);
        is_file($generateName) && unlink($generateName);
        is_file($tempName) && unlink($tempName);

        return $excelGenerated;
    }

    /**
     * エクセルファイルのExport.
     */
    public function download($filename = null)
    {
        isset($filename) || $filename = basename($this->excelName);
        $filename = rawurlencode($filename);

        return response($this->generate())
            ->header('Content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'filename*=UTF-8\'\''.$filename)
            ->header('Cache-Control', 'max-age=0');
    }

    /**
     * エクセルファイルの保存.
     */
    public function save($filename)
    {
        file_put_contents($filename, $this->generate());
    }

    /* Private function */

    private function _get_name($sheetColumn, $sheetRow)
    {
        if (is_integer($sheetColumn)) {
            $columnName = $this->_get_column_name($sheetColumn);
            $rowName = intval($sheetRow) + 1;
        } else {
            $columnName = $sheetColumn;
            $rowName = $sheetRow;
        }

        return [$columnName, $rowName];
    }

    private function _get_worksheet($sheetName)
    {
        if (isset($this->worksheet[$sheetName])) {
            return $this->worksheet[$sheetName];
        }

        if (empty($this->excelTemplate->numFiles)) {
            return false;
        }

        return $this->excelTemplate->getFromName($sheetName);
    }

    private function _get_value_sheet($worksheetXml, $columnName, $rowName)
    {
        $cellName = $columnName.$rowName;

        $rows = $worksheetXml->sheetData->row;
        foreach ($rows as $row) {
            if ($row['r'] != $rowName) {
                continue;
            }

            foreach ($row->c as $cell) {
                if ($cell['r'] == $cellName) {
                    if ('s' == $cell['t']) {
                        //文字列の時
                        return strval($this->_get_value(intval($cell->v)));
                    } else {
                        return strval($cell->v);
                    }
                }
            }
        }

        return false;
    }

    private function _set_value_sheet($worksheetXml, $columnName, $rowName, $value)
    {
        $cellName = $columnName.$rowName;

        $rows = $worksheetXml->sheetData->row;
        foreach ($rows as $row) {
            if ($row['r'] != $rowName) {
                continue;
            }

            foreach ($row->c as $cell) {
                if ($cell['r'] == $cellName) {
                    // 値の置き換え
                    $this->_set_value($cell, $value);

                    return $worksheetXml;
                }
            }

            // セルを追加して値をセット
            $cell = $row->addChild('c');
            $cell['r'] = $cellName;
            $this->_set_value($cell, $value);

            // 列の順序をソート
            $sortCells = [];
            $sortCellNames = [];
            foreach ($row->c as $cell) {
                // ソートのために左詰に変換
                $sortCellName = sprintf('% 20s', strval($cell['r']));
                $sortCellNames[] = $sortCellName;
                $sortCells[$sortCellName] = clone $cell;
            }
            sort($sortCellNames);

            unset($row->c);
            foreach ($sortCellNames as $sortCellName) {
                $this->_append_child($row, $sortCells[$sortCellName]);
            }

            return $worksheetXml;
        }

        // 列を追加して値をセット
        $row = $worksheetXml->sheetData->addChild('row');
        $row['r'] = $rowName;

        $cell = $row->addChild('c');
        $cell['r'] = $cellName;
        $this->_set_value($cell, $value);

        // 行の順序をソート
        $sortRows = [];
        $sortRowNames = [];
        foreach ($rows as $row) {
            $rowName = intval($row['r']);
            $sortRowNames[] = $rowName;
            $sortRows[$rowName] = clone $row;
        }
        sort($sortRowNames);

        unset($worksheetXml->sheetData->row);
        foreach ($sortRowNames as $rowName) {
            $this->_append_child($worksheetXml->sheetData, $sortRows[$rowName]);
        }

        return $worksheetXml;
    }

    private function _get_value($stringIndex)
    {
        // 文字列の共通ファイルに追加
        $sharedName = 'xl/sharedStrings.xml';
        $sharedString = $this->_get_worksheet($sharedName);

        // 共通ファイルがない時は作成
        if (empty($sharedString)) {
            return false;
        }

        $sharedXml = new \SimpleXMLElement($sharedString);

        // 共通ファイルで文字列を検索すること
        $shareNo = 0;
        foreach ($sharedXml->si as $sharedSi) {
            if ($shareNo == $stringIndex) {
                $string = '';

                // 装飾されている文字列を取得
                foreach ($sharedSi->r as $sharedSiR) {
                    isset($sharedSiR->t) && $string .= strval($sharedSiR->t);
                }

                // 装飾されていない文字列を取得
                isset($sharedSi->t) && $string .= strval($sharedSi->t);

                return $string;
            }
            ++$shareNo;
        }

        return false;
    }

    private function _set_value($cell, $value)
    {
        if (is_numeric($value)) {
            $cell->v = $value;
            // 文字列などの指定をなくす
            unset($cell['t']);

            return;
        }

        // 文字列の共通ファイルに追加
        $sharedName = 'xl/sharedStrings.xml';
        $sharedString = $this->_get_worksheet($sharedName);

        // 共通ファイルがない時は作成
        empty($sharedString) && $sharedString = $this->_add_shared();

        $sharedXml = new \SimpleXMLElement($sharedString);

        // SimpleXMLElementの変換もれに対応
        $value = str_replace('&', '&amp;', $value);

        // 共通ファイルで文字列を検索すること
        $shareNo = 0;
        foreach ($sharedXml->si as $sharedSi) {
            if (strval($sharedSi->t) == $value) {
                $stringIndex = $shareNo;
                break;
            }
            ++$shareNo;
        }

        if (empty($stringIndex)) {
            // 文字列を共通ファイルに追加
            $sharedXml['count'] = intval($sharedXml['count']) + 1;
            $sharedXml['uniqueCount'] = intval($sharedXml['uniqueCount']) + 1;

            $addString = $sharedXml->addChild('si');
            $addString->addChild('t', $value);
            $stringIndex = $sharedXml->si->count() - 1;

            $this->worksheet[$sharedName] = $sharedXml->asXML();
        }

        // 文字列を指定
        $cell->v = $stringIndex;
        $cell['t'] = 's';
    }

    private function _get_column_name($columnIndex)
    {
        $columnName = '';
        $currentColIndex = $columnIndex;
        while (true) {
            $alphabetIndex = $currentColIndex % 26;
            $alphabet = chr(ord('A') + $alphabetIndex);
            $columnName = $alphabet.$columnName;
            if ($currentColIndex < 26) {
                break;
            }
            $currentColIndex = intval(floor(($currentColIndex - 26) / 26));
        }

        return $columnName;
    }

    private function _get_column_index($columnName)
    {
        $digitColumn = strlen($columnName) - 1;
        $columnIndex = 0;
        for ($i = 0; $i <= $digitColumn; ++$i) {
            $columnIndex += (ord($columnName[$digitColumn - $i]) - 64) * (26 ** $i);
        }
        --$columnIndex;

        return $columnIndex;
    }

    private function _append_child(\SimpleXMLElement $target, \SimpleXMLElement $addElement)
    {
        if ('' !== strval($addElement)) {
            $child = $target->addChild($addElement->getName(), strval($addElement));
        } else {
            $child = $target->addChild($addElement->getName());
        }

        foreach ($addElement->attributes() as $attName => $attVal) {
            $child->addAttribute(strval($attName), strval($attVal));
        }
        foreach ($addElement->children() as $addChild) {
            $this->_append_child($child, $addChild);
        }
    }

    private function _add_shared()
    {
        $this->addShared = true;

        $contentString = $this->_get_worksheet('[Content_Types].xml');
        $contentString = substr($contentString, 0, -8);
        $contentString .= <<< END_of_HTML
<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>
END_of_HTML;
        $this->worksheet['[Content_Types].xml'] = $contentString;

        $relsString = $this->_get_worksheet('xl/_rels/workbook.xml.rels');
        $relsString = substr($relsString, 0, -16);
        $relsString .= <<< END_of_HTML
<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>
END_of_HTML;
        $this->worksheet['xl/_rels/workbook.xml.rels'] = $relsString;

        $sharedString = <<< END_of_HTML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="0" uniqueCount="0"></sst>
END_of_HTML;

        return $sharedString;
    }
}

/* End of file */
