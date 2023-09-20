<?php
use Medoo\Medoo;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class ReportsCtrl extends Common
{
    private $_NONCE;
    private $_ERRORS = [];
    private $_MONTHS_NAMES = ['1' => 'Jan', '2' => 'Fev', '3' => 'Mar', '4' => 'Abr', '5' => 'Mai', '6' => 'Jun', '7' => 'Jul', '8' => 'Ago', '9' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];
    private $_ALPHABET;
    private $_REPORT_MAPS = [
        "users_subscriptions" => [
            "fields" => [
                ["field" => "id",                           "label" => "ID da Assinatura Painel", "format" => "text"],
                ["field" => "external_id",                  "label" => "ID da Assinatura Vindi", "format" => "text"],
                ["field" => "user_uid",                     "label" => "ID do Usuário", "format" => "text"],
                ["field" => "plan_id",                      "label" => "ID do Plano", "format" => "text"],
                ["field" => "status_id",                    "label" => "ID do Status", "format" => "text"],
                ["field" => "vip",                          "label" => "VIP", "format" => "text"],
                ["field" => "payment_url",                  "label" => "URL Pagamento", "format" => "text"],
                ["field" => "voucher_id",                   "label" => "ID do voucher", "format" => "text"],
                ["field" => "payment_method",               "label" => "Método de Pagamento", "format" => "text"],
                ["field" => "discount_amount",              "label" => "Desconto", "format" => "amount"],
                ["field" => "subtotal_amount",              "label" => "Subtotal", "format" => "amount"],
                ["field" => "total_amount",                 "label" => "Total", "format" => "amount"],
                ["field" => "end_date",                     "label" => "Data término", "format" => "date"],
                ["field" => "created",                      "label" => "Data Criação", "format" => "date"],
                ["field" => "active",                       "label" => "Ativo", "format" => "boolean"],
                ["field" => "cancel_reason",                "label" => "Motivo Cancelamento", "format" => "text"],
                ["field" => "user_name",                    "label" => "Nome completo", "format" => "text"],
                ["field" => "user_company_name",            "label" => "Empresa", "format" => "text"],
                ["field" => "user_document_type",           "label" => "Tipo Documento", "format" => "text"],
                ["field" => "user_document_number",         "label" => "Número Documento", "format" => "text"],
                ["field" => "user_rg",                      "label" => "RG", "format" => "text"],
                ["field" => "user_ie",                      "label" => "IE", "format" => "text"],
                ["field" => "user_email",                   "label" => "E-Mail", "format" => "text"],
                // ["field" => "user_birth_date",              "label" => "Nascimento", "format" => "text"],
                ["field" => "user_gender",                  "label" => "Sexo", "format" => "text"],
                ["field" => "user_phone",                   "label" => "Telefone", "format" => "text"],
                ["field" => "user_exp_met_us",                   "label" => "Onde conheceu a Oxygen?", "format" => "text"],
                ["field" => "subscripions_plans_unit",      "label" => "Periodicidade", "format" => "text"],
                // ["field" => "subscripions_plans_trial_days","label" => "Dias Trial", "format" => "text"],
                ["field" => "subscripions_plans_amount",    "label" => "Valor do Plano", "format" => "amount"],
                ["field" => "subscripions_plans_payment_gateway_id",    "label" => "ID do plano Vindi", "format" => "text"],
                ["field" => "subscripions_plans_title_br",  "label" => "Nome do Plano", "format" => "text"],
                ["field" => "users_addresses_address",      "label" => "Endereço", "format" => "text"],
                ["field" => "users_addresses_number",       "label" => "Número", "format" => "text"],
                ["field" => "users_addresses_complement",   "label" => "Complemento", "format" => "text"],
                ["field" => "users_addresses_postal_code",  "label" => "CEP", "format" => "text"],
                ["field" => "users_addresses_district",     "label" => "Bairro", "format" => "text"],
                ["field" => "users_addresses_city",         "label" => "Cidade", "format" => "text"],
                ["field" => "users_addresses_state",        "label" => "Estado", "format" => "text"],
                ["field" => "users_addresses_country",      "label" => "País", "format" => "text"],
                ["field" => "billing_address",              "label" => "Endereço (billing)", "format" => "text"],
                ["field" => "billing_address_number",       "label" => "Número (billing)", "format" => "text"],
                ["field" => "billing_complement",           "label" => "Complemento (billing)", "format" => "text"],
                ["field" => "billing_postal_code",          "label" => "CEP (billing)", "format" => "text"],
                ["field" => "billing_district",             "label" => "Bairro (billing)", "format" => "text"],
                ["field" => "billing_city",                 "label" => "Cidade (billing)", "format" => "text"],
                ["field" => "billing_state",                "label" => "Estado (billing)", "format" => "text"],
                ["field" => "billing_country",              "label" => "País (billing)", "format" => "text"],
            ],
        ],
        "users_not_subscriscribers" => [
            "fields" => [
                ["field" => "id",                           "label" => "ID da Usuário", "format" => "text"],
                ["field" => "name",                         "label" => "Nome completo", "format" => "text"],
                ["field" => "document_type",                "label" => "Tipo Documento", "format" => "text"],
                ["field" => "document_number",              "label" => "Número Documento", "format" => "text"],
                ["field" => "rg",                           "label" => "RG", "format" => "text"],
                ["field" => "ie",                           "label" => "IE", "format" => "text"],
                ["field" => "email",                        "label" => "E-Mail", "format" => "text"],
                ["field" => "gender",                       "label" => "Sexo", "format" => "text"],
                ["field" => "phone",                        "label" => "Telefone", "format" => "text"],
                ["field" => "exp_met_us",                   "label" => "Onde conheceu a Oxygen?", "format" => "text"]
            ],
        ],
        "giftcards" => [
            "fields" => [
                ["field" => "id",                           "label" => "ID", "format" => "text"],
                ["field" => "method",                       "label" => "Tipo de desconto", "format" => "voucher"],
                ["field" => "voucher",                      "label" => "Código", "format" => "text"],
                ["field" => "discount",                     "label" => "Valor", "format" => "text"],
                ["field" => "expire_date",                  "label" => "Expira em", "format" => "text"],
                ["field" => "cycles",                       "label" => "Ciclos", "format" => "text"],
                ["field" => "buyer_authid",                 "label" => "Usuário presenteador", "format" => "text"]
            ],
        ],
        "journeys" => [
            "fields" => [
                ["field" => "id",                           "label" => "ID", "format" => "text"],
                ["field" => "user_name",                    "label" => "Nome completo", "format" => "text"],
                ["field" => "user_document_number",         "label" => "Número Documento", "format" => "text"],
                ["field" => "user_email",                   "label" => "E-Mail", "format" => "text"],
                ["field" => "user_phone",                   "label" => "Telefone", "format" => "text"],
                ["field" => "title_br",                     "label" => "Destino", "format" => "text"],
                ["field" => "journey_plan",                 "label" => "Pacote", "format" => "text"],
                ["field" => "status_label",                 "label" => "Status", "format" => "text"]
            ],
        ]
    ];

    public function __construct()
    {
        // if(ENVIRONMENT == 'PROD'){
        //     $client = new Redis();
        //     $client->connect('127.0.0.1', 6379);
        //     $pool = new \Cache\Adapter\Redis\RedisCachePool($client);
        //     $simpleCache = new \Cache\Bridge\SimpleCache\SimpleCacheBridge($pool);

        //     \PhpOffice\PhpSpreadsheet\Settings::setCache($simpleCache);
        // }

        $this->_NONCE = uniqid();
        $_ALPHABET = range("A", "Z");
        $this->_ALPHABET = $_ALPHABET;
        foreach ($_ALPHABET as $letter) {
            foreach ($_ALPHABET as $letter2) {
                $this->_ALPHABET[] = $letter.$letter2;
            }
        }
        parent::__construct();

        $this->_COMPLEX_QUERIES = [
            'subscriptions_canceled' => array(
                'query' => 'SELECT * FROM v_report_subscriptions WHERE external_id <> \'vip\' and status_id = '. Subscription::$_STATUS_CANCELED.'  AND id NOT IN ( SELECT id FROM subscriptions WHERE status_id IN ('.Subscription::$_STATUS_ACTIVE.','.Subscription::$_STATUS_INACTIVE.') AND _authid IN ( SELECT _authid FROM subscriptions WHERE status_id = '.Subscription::$_STATUS_CANCELED.')) group by id, _authid ;',
                'variables' => array(),
            ),
            'users_not_subscribers' => array(
                'query' => 'SELECT pwa_users.* FROM pwa_users JOIN auth_users ON pwa_users.id = auth_users.uid AND auth_users.profile = \'pwa\' LEFT JOIN subscriptions ON auth_users.id = subscriptions._authid WHERE subscriptions.id IS NULL;',
                'variables' => array(),
            ),
            'giftcards' => array(
                'query' => 'SELECT shop_vouchers.*, pwa_users.name AS user_name FROM  shop_vouchers  LEFT JOIN auth_users ON shop_vouchers.buyer_authid = auth_users.id  LEFT JOIN pwa_users ON auth_users.uid = pwa_users.id WHERE shop_vouchers.exp_gift = TRUE;',
                'variables' => array(),
            ),
            'aggregate_vindi' => array(
                'query' => 'SELECT sum(`v_report_vindi_invoices`.`amount`) AS `total`, `v_report_vindi_invoices`.`charged_at` AS `charged_at`, `v_report_vindi_invoices`.`payment_method` AS `payment_method`, `v_report_vindi_invoices`.`payment_company` AS `payment_company` FROM `v_report_vindi_invoices` WHERE `nonce` = \'' . $this->_NONCE . '\' group by `v_report_vindi_invoices`.`charged_at`,`v_report_vindi_invoices`.`payment_method`,`v_report_vindi_invoices`.`payment_company` order by `v_report_vindi_invoices`.`charged_at`;',
                'variables' => array(),
            )
        ];
    }

    public function __generate($request, $response, $args)
    {
        set_time_limit(0);
        $result = new StdClass();
        $params = $this->_VARS;

        $where = [];
        $fields = '*';

        $created_alias = 'created';
        switch ($args['alias']) {
            case 'users_subscriptions':
                $fields = ["id", "external_id","user_uid", 'user_exp_met_us',"plan_id","status_id", "vip","payment_url","voucher_id","payment_method","discount_amount","subtotal_amount","total_amount","end_date","created","active","cancel_reason","user_name","user_company_name","user_document_type","user_document_number","user_rg","user_ie","user_email","user_birth_date","user_gender","user_phone","subscripions_plans_unit","subscripions_plans_trial_days","subscripions_plans_amount","subscripions_plans_payment_gateway_id","subscripions_plans_title_br","users_addresses_address","users_addresses_number","users_addresses_complement","users_addresses_postal_code","users_addresses_district","users_addresses_city","users_addresses_state","users_addresses_country","billing_address","billing_address_number","billing_complement","billing_postal_code","billing_district","billing_city","billing_state","billing_country"];
                $ReportCtrl = new VirtualController('v_report_subscriptions');
                $SubscriptionCtrl = new VirtualController('subscriptions');

                $where['deleted'] = 0;

                if ($params->status_id == Subscription::$_STATUS_CANCELED) {
                    $result->report = $this->getByQueryAlias('subscriptions_canceled');
                    $result->url = $this->buildReport($args['alias'], $result->report);
                }

                if ($params->status_id == Subscription::$_STATUS_INACTIVE) {
                    $result->report = $this->getByQueryAlias('users_not_subscribers');
                    $result->url = $this->buildReport('users_not_subscriscribers', $result->report);
                }
                if ($params->status_id == Subscription::$_STATUS_ACTIVE) {
                    if (!empty($params->plan_id)) {
                        $where['plan_id'] = $params->plan_id;
                    }
                    // if ($params->vip == true) {
                    //     $where['external_id'] = 'vip';
                    // }else{
                    //     $where['external_id[!]'] = 'vip';
                    // }
                    $where['status_id'] = Subscription::$_STATUS_ACTIVE;
                    $where['GROUP'] = ['id'];
                    $result->report = $ReportCtrl->get($where, $fields);
                    $result->url = $this->buildReport($args['alias'], $result->report);
                }
                break;
            case 'giftcards':
                $result->report = $this->getByQueryAlias($args['alias']);
                $result->url = $this->buildReport($args['alias'], $result->report);
                break;
            case 'journeys':
                $ReportCtrl = new VirtualController('v_report_journeys_orders');
                $where = [];

                if (!empty($params->transaction_status_id)) {
                    $where['transaction_status_id'] = $params->transaction_status_id;
                }
                if (!empty($params->journey_id) && $params->journey_id[0] != 'all') {
                    $where['journey_id'] = $params->journey_id;
                }

                $result->report = $ReportCtrl->getParsed($where);

                $result->url = $this->buildReport($args['alias'], $result->report);
                break;
        }

        $result->_LOGS = $GLOBALS["_MYSQL_LOGS"];
        $result->_ERRO_LOGS = $GLOBALS["_MYSQL_ERROR_LOGS"];
        return $response->withJson($result);
    }

    public function __generateVindiInvoices($request, $response, $args)
    {
        set_time_limit(0);
        $result = new StdClass();
        $params = $this->_VARS;

        $billsIds = $this->importInvoices($this->_NONCE, $params->startDate, $params->endDate);
        $this->importBills($billsIds, $this->_NONCE);


        $where = ['nonce' => $this->_NONCE];
        $VindiInvoiceCtrl = new VirtualController('v_report_vindi_invoices');
        $VindiInvoiceAggregatedCtrl = new VirtualController('v_report_vindi_invoices_aggregated');

        $fieldsInvoce = [
            'name',
            'email',
            'amount',
            'status',
            'integration_reference',
            'created_at',
            'charged_at',
            'bill_status',
            'payment_method',
            'payment_company',
            'product_item',
            'invoice_id',
            'bill_id',
            'customer_id'
        ];

        $invoicesData = $VindiInvoiceCtrl->get($where, $fieldsInvoce);

        $spreadsheet = new Spreadsheet();

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
            $sheet->setCellValue($this->_ALPHABET[$k_field] . "1", $field);
        }


        foreach ($invoicesData as $k_row => $row) {
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
                $sheet->setCellValue($this->_ALPHABET[$k_column] . $rowNum, $column);
                $k_column++;
            }
        }

        $sheet = $spreadsheet->getSheet(1);

        $headers = [
            'Valor',
            'Data do Faturamento',
            'Método de Pagamento',
            'Bandeira do Cartão'
        ];

        foreach ($headers as $k_field => $field) {
            $sheet->setCellValue($this->_ALPHABET[$k_field] . "1", $field);
        }

        $invoicesAggregateData = $this->getByQueryAlias('aggregate_vindi');

        foreach ($invoicesAggregateData as $k_row => $row) {
            $rowNum = $k_row + 2;
            $row = (object) $row;
            $row->charged_at = date('d/m/Y', strtotime($row->charged_at));
            $k_column = 0;
            foreach ($row as $column) {
                $sheet->setCellValue($this->_ALPHABET[$k_column] . $rowNum, $column);
                $k_column++;
            }
        }


        $writer = new Xlsx($spreadsheet);
        $filename = Helper::salt();
        if (strpos('_original', UPLOAD_URL) >= 0){
            $reportPath = str_replace('_original/','reports/',UPLOAD_URL);
            $fileURL =  str_replace('_original/','reports/', MEDIA_URL) . $filename . '.xlsx';
        }else{
            $reportPath = UPLOAD_URL . 'reports/';
            $fileURL =  MEDIA_URL . 'reports/' . $filename . '.xlsx';
        }
        if (file_exists($reportPath) == false) {
            mkdir($reportPath, 0774);
        }
        $filePath =  $reportPath. $filename . '.xlsx';
        $writer->save($filePath);


        try{
            if(ENVIRONMENT == 'PROD'){
                $fileURL = MEDIA_URL . "reports/" . $filename . '.xlsx';

                $s3 = new S3Client([
                    'version' => 'latest',
                    'region'  => S3_REGION
                ]);

                try {
                    $uploadObject = [
                        'Bucket' => S3_BUCKET,
                        'Key'    => 'reports/'.$filename. '.xlsx',
                        'Body'   => file_get_contents($filePath),
                        'ACL'    => 'public-read'
                    ];
                    $result = $s3->putObject($uploadObject);

                } catch (AwsException $e) {
                }
                unlink($filePath);
            }else{
                $writer->save($filePath);
            }
        }catch(Exception $e ){
            print_r($e);
        }
        $result->url = $fileURL;
        $result->_LOGS = $GLOBALS["_MYSQL_LOGS"];
        $result->_ERRO_LOGS = $GLOBALS["_MYSQL_ERROR_LOGS"];
        return $response->withJson($result);
    }


    public function buildReport($alias, $data)
    {
        // public function usersReport($data) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($this->_REPORT_MAPS[$alias]['fields'] as $k_field => $field) {
            $sheet->setCellValue($this->_ALPHABET[$k_field] . "1", $field['label']);
        }

        $total = 0;
        foreach ($data as $k_row => $row) {
            $row = (object) $row;
            $rowNum = $k_row + 2;
            foreach ($this->_REPORT_MAPS[$alias]['fields'] as $k_field => $field) {
                switch ($field['format']) {
                    case 'date':
                        if (empty($row->{$field['field']})) {
                            $formattedField = "";
                        } else {
                            $formattedField = date('d/m/Y', strtotime($row->{$field['field']}));
                        }
                        break;
                    case 'amount':
                        $formattedField = round($row->{$field['field']} / 100, 2);
                        break;
                    case 'boolean':
                        $formattedField = $row->{$field['field']} == true ? 'Sim' : 'Não';
                        break;
                    case 'voucher':
                        $formattedField = $row->{$field['field']} == 'amount' ? 'Valor' : 'Porcentagem';
                        break;
                    case 'text':
                        $formattedField = $row->{$field['field']};
                        break;
                }
                $sheet->setCellValue($this->_ALPHABET[$k_field] . $rowNum, $formattedField);
            }
            switch ($this->_REPORT_MAPS[$alias]['totalFieldType']) {
                case 'amount':
                case 'sum':
                    $total += $row->{$this->_REPORT_MAPS[$alias]['totalField']};
                    break;
            }
            unset($data[$k_row]);
        }
        if ($this->_REPORT_MAPS[$alias]['totalFieldType']) {
            $colCount = count($this->_REPORT_MAPS[$alias]['fields']);
            $sheet->setCellValue($this->_ALPHABET[0] . ($rowNum + 2), 'Total');
            switch ($this->_REPORT_MAPS[$alias]['totalFieldType']) {
                case 'amount':
                    $sheet->setCellValue($this->_ALPHABET[$colCount - 1] . ($rowNum + 2), round($total / 100, 2));
                    break;
                case 'sum':
                    $sheet->setCellValue($this->_ALPHABET[$colCount - 1] . ($rowNum + 2), $total);
                    break;
                case 'count':
                    $sheet->setCellValue($this->_ALPHABET[$colCount - 1] . ($rowNum + 2), count($data));
                    break;
            }
        }



        $writer = new Xlsx($spreadsheet);
        $filename = Helper::salt();
        if (strpos('_original', UPLOAD_URL) >= 0){
            $reportPath = str_replace('_original/','reports/',UPLOAD_URL);
            $fileURL =  str_replace('_original/','reports/', MEDIA_URL) . $filename . '.xlsx';
        }else{
            $reportPath = UPLOAD_URL . 'reports/';
            $fileURL =  MEDIA_URL . 'reports/' . $filename . '.xlsx';
        }
        if (file_exists($reportPath) == false) {
            mkdir($reportPath, 0774);
        }
        $filePath =  $reportPath. $filename . '.xlsx';
        $writer->save($filePath);


        try{
            if(ENVIRONMENT == 'PROD'){
                $fileURL = MEDIA_URL . "reports/" . $filename . '.xlsx';

                $s3 = new S3Client([
                    'version' => 'latest',
                    'region'  => S3_REGION
                ]);

                try {
                    $uploadObject = [
                        'Bucket' => S3_BUCKET,
                        'Key'    => 'reports/'.$filename. '.xlsx',
                        'Body'   => file_get_contents($filePath),
                        // 'SourceFile' => $filePath,
                        'ACL'    => 'public-read'
                        // 'Content-Type' => getMime($ext)
                    ];
                    // if(!empty($_VARS->extraParams) && $_VARS->extraParams->base64 === false){
                    //     unset($uploadObject['Body']);
                    //     $uploadObject['SourceFile'] =  $_VARS->fileUpload->file;
                    // }
                    // Upload data.
                    $result = $s3->putObject($uploadObject);

                } catch (AwsException $e) {
                    // $result = new StdClass();
                    // print_r($e->getMessage());
                    // exit;
                }
                unlink($filePath);
            }else{
                $writer->save($filePath);
            }
        }catch(Exception $e ){
            print_r($e);
        }
        return $fileURL;
    }

    private function importInvoices($nonce, $startDate, $endDate){
        // $startDate = '2022-10-05';
        // $endDate = '2022-11-05';

        $page = 1;
        $invoicesArr = [];
        $invoiceCtrl = new VirtualController('exp_vindi_invoices');
        $billsIds = [];
        // DELETE ALL INVOICES
        do {
            $invoices = $this->_fetchVindi('/invoices?per_page=50&page=' . $page . '&query=(issued_at>='  .$startDate . ' AND issued_at<=' . $endDate . ')', NULL, 'GET');

            foreach ($invoices->body->invoices as $invoiceData) {
                $billsIds[] = $invoiceData->bill->id;
                $newInvoice = new StdClass();
                $newInvoice->invoice_id = $invoiceData->id;
                $newInvoice->bill_id = $invoiceData->bill->id;
                $newInvoice->customer_id = $invoiceData->customer->id;
                $newInvoice->integration_reference = $invoiceData->integration_reference;
                $newInvoice->email = $invoiceData->customer->email;
                $newInvoice->name = $invoiceData->customer->name;
                $newInvoice->amount = $invoiceData->amount;
                $newInvoice->status = $invoiceData->status;

                $newInvoice->issued_at = substr($invoiceData->issued_at, 0, 10);
                $newInvoice->created_at = substr($invoiceData->created_at, 0, 10);

                $issuedDate=new DateTime($newInvoice->issued_at);
                $createdDate=new DateTime($newInvoice->created_at);
                $Months = $createdDate->diff($issuedDate);
                $chargeDaysToAdd = (($Months->y) * 12) + ($Months->m) * 30; // são de 30 em 30 dias que a STONE cobra a próxima parcela de uma fatura
                $createdDate->modify('+'.$chargeDaysToAdd.' days');
                $newInvoice->charged_at = $createdDate->format('Y-m-d');

                $newInvoice->data = json_encode($invoiceData);
                $newInvoice->nonce = $nonce;
                $invoiceCtrl->saveStructured($newInvoice);
            }
            $page++;
        } while (count($invoices->body->invoices) > 0);
        return $billsIds;
    }

    private function importBills($billsIds, $nonce){

        $page = 1;
        $billCtrl = new VirtualController('exp_vindi_bills');
        $query = [];
        foreach ($billsIds as $billId) {
            $query[] = 'id='.$billId;
        }

        $queryString = implode(' OR ', $query);

        do {
            $bills = $this->_fetchVindi('/bills?per_page=50&page=' . $page . '&query=(' . $queryString . ')', NULL, 'GET');
            foreach ($bills->body->bills as $billData) {
                $newBill = new StdClass();
                $newBill->bill_id = $billData->id;
                $newBill->amount = $billData->amount;
                $newBill->status = $billData->status;
                $newBill->created_at = $billData->created_at;
                $newBill->installments = $billData->installments;
                foreach ($billData->charges as $charge) {
                    if ($billData->status == $charge->status) {
                        $newBill->paid_at = $charge->paid_at;
                        $newBill->payment_method = $charge->payment_method->public_name;
                        $newBill->payment_company = $charge->last_transaction->payment_profile->payment_company->name;
                    }
                }

                $newBill->product_item = $billData->bill_items[0]->product_item->product->name;
                $newBill->data = json_encode($billData);
                $newBill->nonce = $nonce;
                $billCtrl->saveStructured($newBill);
            }
            $page++;
        } while (count($bills->body->bills) > 0);
        return $billsIds;
    }


    function _fetchVindi($endpoint, $data, $type = 'GET')
    {
        // $_VINDI_BASE_URL = VINDI_BASE_URL;
        // $_ACCESS_TOKEN = VINDI_API_KEY;
        $_VINDI_BASE_URL = 'https://app.vindi.com.br/api/v1';
        $_ACCESS_TOKEN = "lfJHpdaXXRi35mpFKLfuHLO9wtRYHTQWO2wNu1g3DJw:";
        $_DEFAULT_COUNTRY = "BR";

        $result = new stdClass();

        $params = array(
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($_ACCESS_TOKEN),
                'Accept'     => 'application/json',
            ]
        );

        if (!empty($data))
            $params['json'] = $data;

        $client = new GuzzleHttp\Client();

        try {
            $res = $client->request($type, $_VINDI_BASE_URL . $endpoint, $params);
            $result->body = json_decode((string) $res->getBody());
            $result->status_code = $res->getStatusCode();
            $result->status = true;
        } catch (RequestException $e) {
            $exception = $e->getResponse()->getBody();
            $result->body = json_decode((string) $exception);
            $result->message = json_decode((string) $e->getMessage());
            $result->status_code = $e->getResponse()->getStatusCode();
            $result->status = false;
            // print_r($result);
        }

        return $result;
    }

}<?php
