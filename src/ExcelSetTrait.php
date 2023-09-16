<?php

namespace Blocs;

/**
 * テンプレートとなるエクセルファイルをとり込んで値の編集ができる
 * グラフや計算処理はエクセルファイルで実行する前提
 */
trait ExcelSetTrait
{
    private $addShared = false;

    /**
     * @param string $sheetNo     シートの番号、左から1,2とカウント
     * @param string $sheetColumn 編集するカラムの列番号、もしくは列名
     * @param string $sheetRow    編集するカラムの行番号、もしくは行名
     * @param string $value       値
     */
    public function set($sheetNo, $sheetColumn, $sheetRow, $value)
    {
        // 指定されたシートの読み込み
        $sheetName = 'xl/worksheets/sheet'.$sheetNo.'.xml';
        $worksheetXml = $this->getWorksheetXml($sheetName);

        // 指定されたシートがない
        if (false === $worksheetXml) {
            return $this;
        }

        // 列番号、行番号を列名、行名に変換
        list($columnName, $rowName) = $this->getName($sheetColumn, $sheetRow);

        // 指定されたセルに値をセット
        $this->worksheetXml[$sheetName] = $this->setValueSheet($worksheetXml, $columnName, $rowName, $value);

        return $this;
    }

    /**
     * エクセルファイルのExport
     */
    public function download($filename = null)
    {
        isset($filename) || $filename = basename($this->excelName);
        $filename = rawurlencode($filename);

        return response($this->generate())
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'filename*=UTF-8\'\''.$filename)
            ->header('Cache-Control', 'max-age=0');
    }

    /**
     * エクセルファイルの保存
     */
    public function save($filename = null)
    {
        isset($filename) || $filename = basename($this->excelName);

        file_put_contents($filename, $this->generate()) && chmod($filename, 0666);
    }

    /**
     * エクセルファイルの生成
     */
    public function generate()
    {
        $excelTemplate = $this->excelTemplate;

        $tempName = tempnam(BLOCS_CACHE_DIR, 'excel');
        $generateName = $tempName.'.zip';
        $excelGenerate = new \ZipArchive();
        $excelGenerate->open($generateName, \ZipArchive::CREATE);

        for ($i = 0; $i < $excelTemplate->numFiles; ++$i) {
            $sheetName = $excelTemplate->getNameIndex($i);
            $worksheetString = $excelTemplate->getFromIndex($i);

            if ('xl/workbook.xml' == $sheetName) {
                $worksheetXml = $this->getWorksheetXml($sheetName);

                if (isset($worksheetXml->calcPr['forceFullCalc'])) {
                    // テンプレートそのままのシート
                    $excelGenerate->addFromString($sheetName, $worksheetString);
                    continue;
                }

                // 強制的に計算させる
                $worksheetXml->calcPr->addAttribute('forceFullCalc', 1);
                $excelGenerate->addFromString($sheetName, $worksheetXml->asXML());

                continue;
            }

            if (isset($this->worksheetXml[$sheetName])) {
                // 値を差し替えたシート
                $excelGenerate->addFromString($sheetName, $this->worksheetXml[$sheetName]->asXML());
                continue;
            }

            // テンプレートそのままのシート
            $excelGenerate->addFromString($sheetName, $worksheetString);
        }

        if ($this->addShared) {
            // 共通文字列のシートを追加
            $excelGenerate->addFromString($this->sharedName, $this->worksheetXml[$this->sharedName]->asXML());
        }

        $excelTemplate->close();
        $excelGenerate->close();

        $excelGenerated = file_get_contents($generateName);
        is_file($generateName) && unlink($generateName);
        is_file($tempName) && unlink($tempName);

        return $excelGenerated;
    }

    private function setValueSheet($worksheetXml, $columnName, $rowName, $value)
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
                    $this->setValue($cell, $value);

                    return $worksheetXml;
                }
            }

            // セルを追加して値をセット
            $cell = $row->addChild('c');
            $cell['r'] = $cellName;
            $this->setValue($cell, $value);

            // 列の順序をソート
            $sortCellList = [];
            $sortCellNameList = [];
            foreach ($row->c as $cell) {
                // ソートのために左詰に変換
                $sortCellName = sprintf('% 20s', strval($cell['r']));
                $sortCellNameList[] = $sortCellName;
                $sortCellList[$sortCellName] = clone $cell;
            }
            sort($sortCellNameList);

            unset($row->c);
            foreach ($sortCellNameList as $sortCellName) {
                $this->appendChild($row, $sortCellList[$sortCellName]);
            }

            return $worksheetXml;
        }

        // 列を追加して値をセット
        $row = $worksheetXml->sheetData->addChild('row');
        $row['r'] = $rowName;

        $cell = $row->addChild('c');
        $cell['r'] = $cellName;
        $this->setValue($cell, $value);

        // 行の順序をソート
        $sortRowList = [];
        $sortRowNameList = [];
        foreach ($rows as $row) {
            $rowName = intval($row['r']);
            $sortRowNameList[] = $rowName;
            $sortRowList[$rowName] = clone $row;
        }
        sort($sortRowNameList);

        unset($worksheetXml->sheetData->row);
        foreach ($sortRowNameList as $rowName) {
            $this->appendChild($worksheetXml->sheetData, $sortRowList[$rowName]);
        }

        return $worksheetXml;
    }

    private function setValue($cell, $value)
    {
        if (is_numeric($value)) {
            $cell->v = $value;
            // 文字列などの指定をなくす
            unset($cell['t']);

            return;
        }

        // 文字列の共通ファイルの読み込み
        $sharedXml = $this->getWorksheetXml($this->sharedName);

        // 共通ファイルがない時は作成
        false === $sharedXml && $sharedXml = $this->addShared();

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

            $this->worksheetXml[$this->sharedName] = $sharedXml;
        }

        // 文字列を指定
        $cell->v = $stringIndex;
        $cell['t'] = 's';
    }

    private function appendChild(\SimpleXMLElement $target, \SimpleXMLElement $addElement)
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
            $this->appendChild($child, $addChild);
        }
    }

    private function addShared()
    {
        $this->addShared = true;

        $contentString = $this->getWorksheetString('[Content_Types].xml');
        $contentString = substr($contentString, 0, -8);
        $contentString .= <<< END_of_HTML
<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>
END_of_HTML;
        $this->worksheetXml['[Content_Types].xml'] = new \SimpleXMLElement($contentString);

        $relsString = $this->getWorksheetString('xl/_rels/workbook.xml.rels');
        $relsString = substr($relsString, 0, -16);
        $relsString .= <<< END_of_HTML
<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>
END_of_HTML;
        $this->worksheetXml['xl/_rels/workbook.xml.rels'] = new \SimpleXMLElement($relsString);

        $sharedString = <<< END_of_HTML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="0" uniqueCount="0"></sst>
END_of_HTML;
        $this->worksheetXml[$this->sharedName] = new \SimpleXMLElement($sharedString);

        return $this->worksheetXml[$this->sharedName];
    }
}
