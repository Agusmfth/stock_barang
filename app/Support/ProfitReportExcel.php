<?php

namespace App\Support;

class ProfitReportExcel
{
    public function make(array $data): string
    {
        $rows = [];
        $rowNumber = 1;
        $rows[] = $this->row($rowNumber++, [['STOCKFLOW ERP — LAPORAN KEUNTUNGAN', 's' => 1]]);
        $rows[] = $this->row($rowNumber++, [['Periode: '.$data['periodLabel'].' | Dibuat: '.now()->format('d/m/Y H:i').' WIB']]);
        $rowNumber++;
        $rows[] = $this->row($rowNumber++, [['OMZET', 's' => 2], ['MODAL BARANG TERJUAL', 's' => 2], ['LABA KOTOR', 's' => 2], ['JUMLAH TRANSAKSI', 's' => 2]]);
        $rows[] = $this->row($rowNumber++, [[$data['totalRevenue'], 'n' => true, 's' => 3], [$data['totalCost'], 'n' => true, 's' => 3], [$data['totalProfit'], 'n' => true, 's' => 4], [$data['totalTransactions'], 'n' => true, 's' => 2]]);
        $rowNumber += 2;

        $rows[] = $this->row($rowNumber++, [['RINGKASAN PER HARI', 's' => 5]]);
        $rows[] = $this->row($rowNumber++, [['Tanggal', 's' => 6], ['Transaksi', 's' => 6], ['Omzet (Rp)', 's' => 6], ['Modal (Rp)', 's' => 6], ['Laba Kotor (Rp)', 's' => 6]]);
        foreach ($data['rows'] as $day) {
            $rows[] = $this->row($rowNumber++, [[$day['date']->format('d/m/Y')], [$day['transactions'], 'n' => true], [$day['revenue'], 'n' => true, 's' => 3], [$day['cost'], 'n' => true, 's' => 3], [$day['profit'], 'n' => true, 's' => 4]]);
        }
        $rows[] = $this->row($rowNumber++, [['Total', 's' => 6], [$data['totalTransactions'], 'n' => true, 's' => 6], [$data['totalRevenue'], 'n' => true, 's' => 3], [$data['totalCost'], 'n' => true, 's' => 3], [$data['totalProfit'], 'n' => true, 's' => 4]]);
        $rowNumber += 2;

        $detailHeaderRow = $rowNumber;
        $rows[] = $this->row($rowNumber++, [['RINCIAN PENJUALAN', 's' => 5]]);
        $detailHeaderRow++;
        $rows[] = $this->row($rowNumber++, array_map(fn ($label) => [$label, 's' => 6], ['Tanggal', 'Jenis', 'No. Transaksi', 'Kasir', 'Produk', 'Qty', 'Omzet (Rp)', 'Modal (Rp)', 'Laba (Rp)']));
        foreach ($data['details'] as $detail) {
            $rows[] = $this->row($rowNumber++, [
                [$detail['date']->format('d/m/Y')], [$detail['kind']], [$detail['reference']], [$detail['cashier']],
                [$detail['product']], [$detail['qty'], 'n' => true], [$detail['revenue'], 'n' => true, 's' => 3],
                [$detail['cost'], 'n' => true, 's' => 3], [$detail['profit'], 'n' => true, 's' => 4],
            ]);
        }
        $lastRow = max($detailHeaderRow, $rowNumber - 1);
        $xmlRows = implode('', $rows);
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><cols><col min="1" max="1" width="14" customWidth="1"/><col min="2" max="2" width="12" customWidth="1"/><col min="3" max="4" width="20" customWidth="1"/><col min="5" max="5" width="30" customWidth="1"/><col min="6" max="6" width="10" customWidth="1"/><col min="7" max="9" width="19" customWidth="1"/></cols><sheetData>'.$xmlRows.'</sheetData><autoFilter ref="A'.$detailHeaderRow.':I'.$lastRow.'"/><mergeCells count="1"><mergeCell ref="A1:I1"/></mergeCells></worksheet>';

        return $this->zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan Keuntungan" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0;[Red]-&quot;Rp&quot; #,##0"/></numFmts><fonts count="3"><font><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><b/><sz val="10"/><name val="Aptos"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF49368F"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF0EDFF"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD9D5E5"/></left><right style="thin"><color rgb="FFD9D5E5"/></right><top style="thin"><color rgb="FFD9D5E5"/></top><bottom style="thin"><color rgb="FFD9D5E5"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7"><xf numFmtId="0" fontId="1" fillId="2" borderId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="1" fillId="2" borderId="0"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="1" applyNumberFormat="1"/><xf numFmtId="164" fontId="2" fillId="3" borderId="1" applyNumberFormat="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0"/><xf numFmtId="0" fontId="2" fillId="2" borderId="1" applyAlignment="1"><alignment vertical="center"/></xf></cellXfs></styleSheet>',
            'xl/worksheets/sheet1.xml' => $sheet,
        ]);
    }

    private function row(int $number, array $cells): string
    {
        $xml = '<row r="'.$number.'">';
        foreach ($cells as $index => $cell) {
            $reference = $this->column($index + 1).$number;
            $style = isset($cell['s']) ? ' s="'.$cell['s'].'"' : '';
            if ($cell['n'] ?? false) {
                $xml .= '<c r="'.$reference.'"'.$style.'><v>'.(float) $cell[0].'</v></c>';
            } else {
                $value = htmlspecialchars((string) $cell[0], ENT_XML1 | ENT_COMPAT, 'UTF-8');
                $xml .= '<c r="'.$reference.'" t="inlineStr"'.$style.'><is><t xml:space="preserve">'.$value.'</t></is></c>';
            }
        }

        return $xml.'</row>';
    }

    private function column(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $number--;
            $column = chr(65 + ($number % 26)).$column;
            $number = intdiv($number, 26);
        }

        return $column;
    }

    private function zip(array $files): string
    {
        $body = '';
        $directory = '';
        $count = 0;
        $dosTime = (int) (date('G') << 11 | date('i') << 5 | floor(date('s') / 2));
        $dosDate = (int) (((int) date('Y') - 1980) << 9 | (int) date('n') << 5 | (int) date('j'));

        foreach ($files as $name => $contents) {
            $name = str_replace('\\', '/', $name);
            $compressed = gzdeflate($contents, 6);
            $crc = crc32($contents);
            $nameLength = strlen($name);
            $offset = strlen($body);
            $body .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 8, $dosTime, $dosDate, $crc, strlen($compressed), strlen($contents), $nameLength, 0).$name.$compressed;
            $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 8, $dosTime, $dosDate, $crc, strlen($compressed), strlen($contents), $nameLength, 0, 0, 0, 0, 0, $offset).$name;
            $count++;
        }

        return $body.$directory.pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($directory), strlen($body), 0);
    }
}
