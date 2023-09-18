<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require 'vendor/autoload.php';
class VindiExport {
    public function __construct() {
        $arguments = array(
            'VINDI_API_KEY' => get_option('_vindi_token'),
            'VINDI_API_URI' => 'https://app.vindi.com.br/api/v1/'
        );
        $Invoice = new Vindi\Invoice($arguments);
        $Bills = new Vindi\Bill($arguments);
        $i = 1;
        $Invoces = $Invoice->all(['query' => 'created_at>="2023-09-10 14:00:00" created_at<"2023-09-28 14:00:00"', 'per_page' => 50, 'paged'=>$i]);
        $items = [];


        foreach ($Invoces as $i => $item) {
            $Invoces[$i]->bill_data = $Bills->get($item->bill->id);
        }
        foreach ($Invoces as $item) {
            $items[] = $this->get_data($item);
        }


        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheetIndex = $spreadsheet->getIndex(
            $spreadsheet->getSheetByName('Worksheet')
        );
        $spreadsheet->removeSheetByIndex($sheetIndex);

        $newWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Lista');
        $spreadsheet->addSheet($newWorkSheet, 0);

        $newWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Agregado');
        $spreadsheet->addSheet($newWorkSheet, 1);

        $sheet = $spreadsheet->getSheet(0);

        $headers = [
            'Nome',
            'E-mail',
            'Valor',
            'Status da Nota',
            'Número',
            'Data de Geração',
            'Data do Faturamento',
            'Status do Faturamento',
            'Método de Pagamento',
            'Bandeira do Cartão',
            'Produto',
            'InvoiceID',
            'BillID',
            'CustomerID',
            'Data do Vencimento'
        ];

        foreach ($headers as $k_field => $field) {
            $sheet->setCellValue($this->columnIndexToExcelCoordinate($k_field) . "1", $field);
        }

        foreach ($items as $k_row => $row) {
            $rowNum = $k_row + 2;
            $row = (object) $row;

            if ($row->payment_method != 'Cartão de crédito') {
                $row->due_at = "À Vista";
            } else {
                $chargedDate = new DateTime($row->charged_at);
                $chargedDate->modify('+30 days');
                $row->due_at = $chargedDate->format('d/m/Y');
            }

            $row->created_at = date('d/m/Y', strtotime($row->created_at));
            $row->charged_at = date('d/m/Y', strtotime($row->charged_at));

            $k_column = 0;
            foreach ($row as $column) {
                $sheet->setCellValue($this->columnIndexToExcelCoordinate($k_column) . $rowNum, $column);
                $k_column++;
            }
        }

        $writer = new Xlsx($spreadsheet);

        $upload_dir = wp_upload_dir();

        $subdirectory = 'planilhas';
        $target_dir = $upload_dir['basedir'] . '/' . $subdirectory;
        wp_mkdir_p($target_dir);

       $writer->save($target_dir.'/file.xlsx');
    }



    public function get_data($item) {
        $fieldsInvoce = [
            'name' => $item->customer->name,
            'email'  => $item->customer->email,
            'amount' => $item->amount,
            'status' =>  $item->status,
            'integration_reference'=> $item->integration_reference,
            'created_at' => $item->created_at,
            'charged_at' => $item->updated_at,
            'bill_status' => $item->bill_data->status,
            'payment_method' => $item->bill_data->charges[0]->payment_method->name,
            'payment_company'=> $item->bill_data->charges[0]->last_transaction->payment_profile->payment_company->name,
            'product_item' => $item->bill_data->bill_items[0]->product_item->product->name,
            'invoice_id' => $item->id,
            'bill_id' => $item->bill_data->id,
            'customer_id' => $item->customer->id
        ];

        return $fieldsInvoce;
    }

    public function columnIndexToExcelCoordinate($index) {
        $dividend = $index + 1;
        $column = '';

        while ($dividend > 0) {
            $modulo = ($dividend - 1) % 26;
            $column = chr(65 + $modulo) . $column;
            $dividend = intval(($dividend - $modulo) / 26);
        }

        return $column;
    }

}

?>