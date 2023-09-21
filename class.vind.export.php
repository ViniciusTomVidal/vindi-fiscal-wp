<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require 'vendor/autoload.php';

class VindiExport
{

    private $arguments = [];

    public function __construct()
    {

        $this->arguments = array(
            'VINDI_API_KEY' => get_option('_vindi_token'),
            'VINDI_API_URI' => 'https://app.vindi.com.br/api/v1/'
        );

        $items = [];
        $Invoices = $this->import_invoices();
        $bill_ids = [];

        foreach ($Invoices as $i => $item) {
            $bill_ids[] = $item->bill->id;
        }

        $Bills = $this->import_bills($bill_ids);

        $billMap = array_column($Bills, null, 'id');

        foreach ($Invoices as $i => $Invoice) {
            $billId = $Invoice->bill->id;
            if (isset($billMap[$billId])) {
                $Invoices[$i]->bill_data = $billMap[$billId];
            }
            $items[] = $this->get_data($Invoice);
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
            $row = (object)$row;

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

        $writer->save($target_dir . '/file-'.explode(" ", $this->date_s($_POST['date_start']))[0].'-'.explode(" ", $this->date_s($_POST['date_end']))[0].'.xlsx');
    }


    public function get_data($item)
    {
        $fieldsInvoce = [
            'name' => $item->customer->name,
            'email' => $item->customer->email,
            'amount' => $item->amount,
            'status' => $item->status,
            'integration_reference' => $item->integration_reference,
            'created_at' => $item->issued_at,
            'charged_at' => null,
            'bill_status' => $item->bill_data->status,
            'payment_method' => null,
            'payment_company' => null,
            'product_item' => $item->bill_data->bill_items[0]->product_item->product->name,
            'invoice_id' => $item->id,
            'bill_id' => $item->bill_data->id,
            'customer_id' => $item->customer->id
        ];

        $newBill = new stdClass();
        foreach ($item->bill_data->charges as $charge) {
            if ($item->bill_data->status == $charge->status) {
                $newBill->paid_at = $charge->paid_at;
                $newBill->payment_method = $charge->payment_method->public_name;
                $newBill->payment_company = $charge->last_transaction->payment_profile->payment_company->name;
            }
        }

        $fieldsInvoce['payment_method'] = $newBill->payment_method;
        $fieldsInvoce['payment_company'] = $newBill->payment_company;

        $issuedDate=new DateTime($fieldsInvoce['charged_at']);
        $createdDate=new DateTime($fieldsInvoce['charged_at']);
        $Months = $createdDate->diff($issuedDate);
        $chargeDaysToAdd = (($Months->y) * 12) + ($Months->m) * 30; // são de 30 em 30 dias que a STONE cobra a próxima parcela de uma fatura
        $createdDate->modify('+'.$chargeDaysToAdd.' days');
        $fieldsInvoce['charged_at'] = $createdDate->format('Y-m-d');


        return $fieldsInvoce;
    }

    public function columnIndexToExcelCoordinate($index)
    {
        $dividend = $index + 1;
        $column = '';

        while ($dividend > 0) {
            $modulo = ($dividend - 1) % 26;
            $column = chr(65 + $modulo) . $column;
            $dividend = intval(($dividend - $modulo) / 26);
        }

        return $column;
    }


    public function date_s($inputDateTime)
    {
        $dateTime = date_create_from_format('Y-m-d\TH:i', $inputDateTime);
        $formattedDateTime = date_format($dateTime, 'Y-m-d H:i:s');

        return $formattedDateTime;
    }

    public function import_invoices()
    {
        $Invoice = new Vindi\Invoice($this->arguments);
        $i = 1;
        $InvoicesRe = [];
        do {
            $Invoces = $Invoice->all(['query' => '(issued_at >= "' . $this->date_s($_POST['date_start']) . '" AND issued_at <="' . $this->date_s($_POST['date_end']) . '")', 'per_page' => 50, 'page' => $i]);
            foreach ($Invoces as $invoce) {
                $InvoicesRe[] = $invoce;
            }
            $i++;
        } while (count($Invoces) > 0);

        return $InvoicesRe;
    }

    public function import_bills($billIds)
    {
        $Bill = new Vindi\Bill($this->arguments);
        $i = 1;
        $BillsRe = [];

        foreach ($billIds as $billId) {
            $query[] = 'id=' . $billId;
        }
        $queryString = implode(' OR ', $query);

        do {
            $Bills = $Bill->all(['query' => "({$queryString})", 'per_page' => 50, 'page' => $i]);
            foreach ($Bills as $invoce) {
                $BillsRe[] = $invoce;
            }
            $i++;
        } while (count($Bills) > 0);

        return $BillsRe;
    }

}

?>