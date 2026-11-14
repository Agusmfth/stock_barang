<?php

namespace App\Support;

class OperationalReportExcel
{
    public function make(array $data): string
    {
        $type = $data['type'];
        $headers = match ($type) {
            'inventory' => ['Kode', 'Produk', 'Kategori', 'Stok', 'Harga Modal (Rp)', 'Nilai Stok (Rp)'],
            'inventory-history' => ['Tanggal', 'Saldo Unit', 'Barang Masuk', 'Barang Keluar', 'Nilai Persediaan (Rp)'],
            default => ['Tanggal', 'Referensi', 'Produk', 'Qty', 'Harga / Unit (Rp)', 'Subtotal (Rp)', 'Dicatat Oleh'],
        };

        $summary = match ($type) {
            'inventory' => [['Jenis Produk', $data['totalProducts']], ['Total Unit', $data['totalUnits']], ['Nilai Persediaan (Rp)', $data['totalValue']]],
            'inventory-history' => [['Barang Masuk', $data['totalInbound']], ['Barang Keluar', $data['totalOutbound']], ['Saldo Akhir', $data['totalUnits']], ['Nilai Akhir (Rp)', $data['totalValue']]],
            default => [['Jumlah Transaksi', $data['totalTransactions']], ['Total Unit', $data['totalUnits']], ['Total Nilai (Rp)', $data['totalAmount']]],
        };

        $bodyRows = [];
        if ($type === 'inventory') {
            foreach ($data['rows'] as $product) {
                $bodyRows[] = [$product->code, $product->name, $product->category ?: '-', ['v' => $product->current_stock, 'n' => true], ['v' => (float) $product->cost_price, 'n' => true, 'currency' => true], ['v' => $product->current_stock * (float) $product->cost_price, 'n' => true, 'currency' => true]];
            }
        } elseif ($type === 'inventory-history') {
            foreach ($data['rows'] as $item) {
                $bodyRows[] = [$item['date']->format('d/m/Y'), ['v' => $item['units'], 'n' => true], ['v' => $item['inbound'], 'n' => true], ['v' => $item['outbound'], 'n' => true], ['v' => $item['value'], 'n' => true, 'currency' => true]];
            }
        } else {
            foreach ($data['rows'] as $transaction) {
                foreach ($transaction->items as $item) {
                    $bodyRows[] = [
                        $transaction->transaction_date->format('d/m/Y'),
                        $transaction->reference_no ?: 'TR-'.$transaction->id,
                        $item->product?->name ?? 'Produk dihapus',
                        ['v' => $item->qty, 'n' => true],
                        ['v' => (float) $item->price, 'n' => true, 'currency' => true],
                        ['v' => $item->qty * (float) $item->price, 'n' => true, 'currency' => true],
                        $transaction->user?->name ?? '-',
                    ];
                }
            }
        }

        $columnCount = count($headers);
        $columnEnd = $this->column($columnCount);
        $sheetRows = [];
        $rowIndex = 1;
        $sheetRows[] = $this->row($rowIndex++, [['v' => 'STOCKFLOW ERP — LAPORAN OPERASIONAL', 's' => 1]]);
        $sheetRows[] = $this->row($rowIndex++, [['v' => $data['periodLabel'].' | Dicetak '.now()->format('d/m/Y H:i').' WIB']]);
        $rowIndex++;
        $summaryHeaders = array_map(fn ($item) => ['v' => strtoupper($item[0]), 's' => 2], $summary);
        $sheetRows[] = $this->row($rowIndex++, $summaryHeaders);
        $summaryValues = array_map(fn ($item) => ['v' => $item[1], 'n' => is_numeric($item[1]), 's' => is_numeric($item[1]) ? 3 : 0, 'currency' => str_contains($item[0], '(Rp)')], $summary);
        $sheetRows[] = $this->row($rowIndex++, $summaryValues);
        $rowIndex++;
        $sheetRows[] = $this->row($rowIndex++, [['v' => 'RINCIAN DATA', 's' => 4]]);
        $headerRow = $rowIndex;
        $sheetRows[] = $this->row($rowIndex++, array_map(fn ($header) => ['v' => $header, 's' => 5], $headers));

        foreach ($bodyRows as $bodyRow) {
            $cells = array_map(function ($value) {
                if (is_array($value)) {
                    return ['v' => $value['v'], 'n' => $value['n'] ?? false, 's' => ($value['currency'] ?? false) ? 6 : 0];
                }

                return ['v' => $value];
            }, $bodyRow);
            $sheetRows[] = $this->row($rowIndex++, $cells);
        }

        $lastRow = max($headerRow, $rowIndex - 1);
        $widths = $type === 'inventory-history' ? [16, 16, 18, 18, 24] : ($type === 'inventory' ? [18, 32, 22, 14, 23, 23] : [16, 20, 32, 12, 22, 22, 24]);
        $columns = '';
        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $columns .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols>'.$columns.'</cols><sheetData>'.implode('', $sheetRows).'</sheetData><autoFilter ref="A'.$headerRow.':'.$columnEnd.$lastRow.'"/><mergeCells count="1"><mergeCell ref="A1:'.$columnEnd.'1"/></mergeCells></worksheet>';

        return $this->zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan Operasional" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => $this->styles(),
            'xl/worksheets/sheet1.xml' => $sheet,
        ]);
    }

    private function row(int $number, array $cells): string
    {
        $xml = '<row r="'.$number.'">';
        foreach ($cells as $index => $cell) {
            $reference = $this->column($index + 1).$number;
            $style = isset($cell['s']) && $cell['s'] ? ' s="'.$cell['s'].'"' : '';
            if ($cell['n'] ?? false) {
                $numberFormat = ($cell['currency'] ?? false) ? ' s="5"' : $style;
                $xml .= '<c r="'.$reference.'"'.$numberFormat.'><v>'.(float) $cell['v'].'</v></c>';
            } else {
                $value = htmlspecialchars((string) ($cell['v'] ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
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

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0;[Red]-&quot;Rp&quot; #,##0"/></numFmts><fonts count="3"><font><sz val="10"/><name val="Aptos"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Aptos Display"/></font><font><b/><sz val="10"/><name val="Aptos"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF49368F"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF0EDFF"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD9D5E5"/></left><right style="thin"><color rgb="FFD9D5E5"/></right><top style="thin"><color rgb="FFD9D5E5"/></top><bottom style="thin"><color rgb="FFD9D5E5"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7"><xf numFmtId="0" fontId="1" fillId="2" borderId="0"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0"/><xf numFmtId="0" fontId="2" fillId="2" borderId="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="1" applyNumberFormat="1"/><xf numFmtId="0" fontId="2" fillId="3" borderId="1"/></cellXfs></styleSheet>';
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
