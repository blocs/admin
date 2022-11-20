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
    private $excel_name;
    private $excel_template;

    private $worksheet = [];
    private $worksheet_xml = [];
    private $add_shared = false;

    /**
     * コンストラクタ
     *
     * テンプレートとなるエクセルファイルの取り込み。
     *
     * @param string $excel_name テンプレートファイル名
     */
    public function __construct($excel_name)
    {
        defined('TEMPLATE_CACHE_DIR') || define('TEMPLATE_CACHE_DIR', config('view.compiled'));

        $this->excel_name = $excel_name;
        $this->excel_template = new \ZipArchive();
        $this->excel_template->open($excel_name);
    }

    /**
     * テンプレートとなるエクセルファイルの値を編集.
     *
     * @param string $sheet_no     シートの番号、左から1,2とカウント
     * @param string $sheet_column 編集するカラムの列番号、もしくは列名
     * @param string $sheet_row    編集するカラムの行番号、もしくは行名
     */
    public function get($sheet_no, $sheet_column, $sheet_row)
    {
        $sheet_name = 'xl/worksheets/sheet'.$sheet_no.'.xml';

        if (isset($this->worksheet_xml[$sheet_name])) {
            // キャッシュを読み込み
            $worksheet_xml = $this->worksheet_xml[$sheet_name];
        } else {
            $worksheet_string = $this->_get_worksheet($sheet_name);

            // シートがない時
            if (empty($worksheet_string)) {
                return false;
            }

            $worksheet_xml = new \SimpleXMLElement($worksheet_string);

            // キャッシュを作成
            $this->worksheet_xml[$sheet_name] = $worksheet_xml;
        }

        // 列番号、行番号を列名、行名に変換
        list($column_name, $row_name) = $this->_get_name($sheet_column, $sheet_row);

        // 指定されたセルの値を取得
        $value = $this->_get_value_sheet($worksheet_xml, $column_name, $row_name);

        return $value;
    }

    /**
     * テンプレートとなるエクセルファイルの値を編集.
     *
     * @param string $sheet_no     シートの番号、左から1,2とカウント
     * @param string $sheet_column 編集するカラムの列番号、もしくは列名
     * @param string $sheet_row    編集するカラムの行番号、もしくは行名
     * @param string $value        値
     */
    public function set($sheet_no, $sheet_column, $sheet_row, $value)
    {
        $sheet_name = 'xl/worksheets/sheet'.$sheet_no.'.xml';

        if (isset($this->worksheet_xml[$sheet_name])) {
            // キャッシュを読み込み
            $worksheet_xml = $this->worksheet_xml[$sheet_name];
        } else {
            $worksheet_string = $this->_get_worksheet($sheet_name);

            // シートがない時
            if (empty($worksheet_string)) {
                return $this;
            }

            $worksheet_xml = new \SimpleXMLElement($worksheet_string);
        }

        // 列番号、行番号を列名、行名に変換
        list($column_name, $row_name) = $this->_get_name($sheet_column, $sheet_row);

        // 指定されたセルに値をセット
        $worksheet_xml = $this->_set_value_sheet($worksheet_xml, $column_name, $row_name, $value);

        $this->worksheet[$sheet_name] = $worksheet_xml->asXML();

        // キャッシュを更新
        $this->worksheet_xml[$sheet_name] = $worksheet_xml;

        return $this;
    }

    /**
     * エクセルファイルの生成.
     */
    public function generate()
    {
        $excel_template = $this->excel_template;
        $worksheet = $this->worksheet;

        $temp_name = tempnam(TEMPLATE_CACHE_DIR, 'excel');
        $generate_name = $temp_name.'.zip';
        $excel_generate = new \ZipArchive();
        $excel_generate->open($generate_name, \ZipArchive::CREATE);

        for ($i = 0; $i < $excel_template->numFiles; ++$i) {
            $sheet_name = $excel_template->getNameIndex($i);
            $worksheet_string = $excel_template->getFromIndex($i);

            if ('xl/workbook.xml' == $sheet_name) {
                $worksheet_xml = new \SimpleXMLElement($worksheet_string);

                if (isset($worksheet_xml->calcPr['forceFullCalc'])) {
                    // テンプレートそのままのシート
                    $excel_generate->addFromString($sheet_name, $worksheet_string);
                } else {
                    // 強制的に計算させる
                    $worksheet_xml->calcPr->addAttribute('forceFullCalc', 1);

                    $excel_generate->addFromString($sheet_name, $worksheet_xml->asXML());
                }

                continue;
            }

            if (isset($worksheet[$sheet_name])) {
                // 値を差し替えたシート
                $excel_generate->addFromString($sheet_name, $worksheet[$sheet_name]);
                continue;
            }

            // テンプレートそのままのシート
            $excel_generate->addFromString($sheet_name, $worksheet_string);
        }

        if ($this->add_shared) {
            // 共通文字列のシートを追加
            $excel_generate->addFromString('xl/sharedStrings.xml', $worksheet['xl/sharedStrings.xml']);
        }

        $excel_template->close();
        $excel_generate->close();

        $excel_generated = file_get_contents($generate_name);
        is_file($generate_name) && unlink($generate_name);
        is_file($temp_name) && unlink($temp_name);

        return $excel_generated;
    }

    /**
     * エクセルファイルのExport.
     */
    public function download($filename = null)
    {
        isset($filename) || $filename = basename($this->excel_name);
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

    private function _get_name($sheet_column, $sheet_row)
    {
        if (is_integer($sheet_column)) {
            $column_name = $this->_get_column_name($sheet_column);
            $row_name = intval($sheet_row) + 1;
        } else {
            $column_name = $sheet_column;
            $row_name = $sheet_row;
        }

        return [$column_name, $row_name];
    }

    private function _get_worksheet($sheet_name)
    {
        if (isset($this->worksheet[$sheet_name])) {
            return $this->worksheet[$sheet_name];
        }

        if (empty($this->excel_template->numFiles)) {
            return false;
        }

        return $this->excel_template->getFromName($sheet_name);
    }

    private function _get_value_sheet($worksheet_xml, $column_name, $row_name)
    {
        $cell_name = $column_name.$row_name;

        $rows = $worksheet_xml->sheetData->row;
        foreach ($rows as $row) {
            if ($row['r'] != $row_name) {
                continue;
            }

            foreach ($row->c as $cell) {
                if ($cell['r'] == $cell_name) {
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

    private function _set_value_sheet($worksheet_xml, $column_name, $row_name, $value)
    {
        $cell_name = $column_name.$row_name;

        $rows = $worksheet_xml->sheetData->row;
        foreach ($rows as $row) {
            if ($row['r'] != $row_name) {
                continue;
            }

            foreach ($row->c as $cell) {
                if ($cell['r'] == $cell_name) {
                    // 値の置き換え
                    $this->_set_value($cell, $value);

                    return $worksheet_xml;
                }
            }

            // セルを追加して値をセット
            $cell = $row->addChild('c');
            $cell['r'] = $cell_name;
            $this->_set_value($cell, $value);

            // 列の順序をソート
            $sort_cells = [];
            $sort_cell_names = [];
            foreach ($row->c as $cell) {
                // ソートのために左詰に変換
                $sort_cell_name = sprintf('% 20s', strval($cell['r']));
                $sort_cell_names[] = $sort_cell_name;
                $sort_cells[$sort_cell_name] = clone $cell;
            }
            sort($sort_cell_names);

            unset($row->c);
            foreach ($sort_cell_names as $sort_cell_name) {
                $this->_append_child($row, $sort_cells[$sort_cell_name]);
            }

            return $worksheet_xml;
        }

        // 列を追加して値をセット
        $row = $worksheet_xml->sheetData->addChild('row');
        $row['r'] = $row_name;

        $cell = $row->addChild('c');
        $cell['r'] = $cell_name;
        $this->_set_value($cell, $value);

        // 行の順序をソート
        $sort_rows = [];
        $sort_row_names = [];
        foreach ($rows as $row) {
            $row_name = intval($row['r']);
            $sort_row_names[] = $row_name;
            $sort_rows[$row_name] = clone $row;
        }
        sort($sort_row_names);

        unset($worksheet_xml->sheetData->row);
        foreach ($sort_row_names as $row_name) {
            $this->_append_child($worksheet_xml->sheetData, $sort_rows[$row_name]);
        }

        return $worksheet_xml;
    }

    private function _get_value($string_index)
    {
        // 文字列の共通ファイルに追加
        $shared_name = 'xl/sharedStrings.xml';
        $shared_string = $this->_get_worksheet($shared_name);

        // 共通ファイルがない時は作成
        if (empty($shared_string)) {
            return false;
        }

        $shared_xml = new \SimpleXMLElement($shared_string);

        // 共通ファイルで文字列を検索すること
        $share_i = 0;
        foreach ($shared_xml->si as $shared_si) {
            if ($share_i == $string_index) {
                $string = '';

                // 装飾されている文字列を取得
                foreach ($shared_si->r as $shared_si_r) {
                    isset($shared_si_r->t) && $string .= strval($shared_si_r->t);
                }

                // 装飾されていない文字列を取得
                isset($shared_si->t) && $string .= strval($shared_si->t);

                return $string;
            }
            ++$share_i;
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
        $shared_name = 'xl/sharedStrings.xml';
        $shared_string = $this->_get_worksheet($shared_name);

        // 共通ファイルがない時は作成
        empty($shared_string) && $shared_string = $this->_add_shared();

        $shared_xml = new \SimpleXMLElement($shared_string);

        // SimpleXMLElementの変換もれに対応
        $value = str_replace('&', '&amp;', $value);

        // 共通ファイルで文字列を検索すること
        $share_i = 0;
        foreach ($shared_xml->si as $shared_si) {
            if (strval($shared_si->t) == $value) {
                $string_index = $share_i;
                break;
            }
            ++$share_i;
        }

        if (empty($string_index)) {
            // 文字列を共通ファイルに追加
            $shared_xml['count'] = intval($shared_xml['count']) + 1;
            $shared_xml['uniqueCount'] = intval($shared_xml['uniqueCount']) + 1;

            $add_string = $shared_xml->addChild('si');
            $add_string->addChild('t', $value);
            $string_index = $shared_xml->si->count() - 1;

            $this->worksheet[$shared_name] = $shared_xml->asXML();
        }

        // 文字列を指定
        $cell->v = $string_index;
        $cell['t'] = 's';
    }

    private function _get_column_name($column_index)
    {
        $column_name = '';
        $currentColIndex = $column_index;
        while (true) {
            $alphabetIndex = $currentColIndex % 26;
            $alphabet = chr(ord('A') + $alphabetIndex);
            $column_name = $alphabet.$column_name;
            if ($currentColIndex < 26) {
                break;
            }
            $currentColIndex = intval(floor(($currentColIndex - 26) / 26));
        }

        return $column_name;
    }

    private function _get_column_index($column_name)
    {
        $digit_column = strlen($column_name) - 1;
        $column_index = 0;
        for ($i = 0; $i <= $digit_column; ++$i) {
            $column_index += (ord($column_name[$digit_column - $i]) - 64) * (26 ** $i);
        }
        --$column_index;

        return $column_index;
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
        $this->add_shared = true;

        $content_string = $this->_get_worksheet('[Content_Types].xml');
        $content_string = substr($content_string, 0, -8);
        $content_string .= <<< END_of_HTML
<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>
END_of_HTML;
        $this->worksheet['[Content_Types].xml'] = $content_string;

        $rels_string = $this->_get_worksheet('xl/_rels/workbook.xml.rels');
        $rels_string = substr($rels_string, 0, -16);
        $rels_string .= <<< END_of_HTML
<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>
END_of_HTML;
        $this->worksheet['xl/_rels/workbook.xml.rels'] = $rels_string;

        $shared_string = <<< END_of_HTML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="0" uniqueCount="0"></sst>
END_of_HTML;

        return $shared_string;
    }
}

/* End of file */
