<?php

namespace Apps\Tms\Packages\Btcextractor;

use System\Base\BasePackage;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;

class Btcextractor extends BasePackage
{
    protected $dataDir = 'apps/Tms/Packages/Btcextractor/Data/';

    public function process()
    {
        // ini_set('xdebug.var_display_max_children', -1); // Unlimited children (array elements/properties)
        // ini_set('xdebug.var_display_max_data', -1);     // Unlimited string length
        // ini_set('xdebug.var_display_max_depth', -1);    // Unlimited nesting depth

        //Increase Exectimeout to 5 hours as this process takes time to extract and merge data.
        if ((int) ini_get('max_execution_time') < 18000) {
            set_time_limit(18000);
        }

        //Increase memory_limit to 1G as the process takes a bit of memory to process the array.
        if ((int) ini_get('memory_limit') < 2048) {
            ini_set('memory_limit', '2048M');
        }
        // Extract Data
        // $this->extractData();

        // Read Companies and Customers files.
        // $this->importCompaniesData();

        // Read Uom
        // $this->importUom();
        // Read Lt
        // $this->importLt();
        // Add Customer Address_References from fromdest and todest from invoices.
        // $this->importAddressReferencesFromInvoices();
        // Add Vehicle information from invoices.
        // $this->importVehicleFromInvoices();
        // Add LR information from invoices.
        // $this->importLrFromInvoices();
        trace(['done']);
    }

    protected function extractData()
    {
        //Extract Data from DBF Files
        try {
            //Extract Company Data
            $data = [];

            $table = new \XBase\TableReader(base_path($this->dataDir . 'BaggaCarriers/BILL/DATA/COMPANY.DBF'));

            while ($record = $table->nextRecord()) {
                $data['companies']['data'][$record->get('companycd')] = $record->getData();
            }
            $data['companies']['totalEntries'] = count($data['companies']['data']);

            $filesToScan = ['contract.dbf','customer.dbf','invhead.dbf','invdet.dbf','uom.dbf','destn.dbf','loadtype.dbf','pay_recpt.dbf'];

            try {
                $dirs = $this->basepackages->utils->scanDir($this->dataDir . 'BaggaCarriers/BILL/DATA/');

                sort($dirs['dirs']);

                if (count($dirs['dirs']) > 0) {
                    foreach ($dirs['dirs'] as $dirKey => $dir) {
                        if (!str_ends_with($dir, 'STRUE')) {
                            $files = $this->basepackages->utils->scanDir($dir, false, ['.BAK','.CDX','.FPT'])['files'];

                            if (count($files) > 0) {
                                $filesToProcess = [];
                                foreach ($files as $file) {
                                    if (str_ends_with($file, '.DBF')) {
                                        $fileName = strtolower(str_replace($dir . '/', '', $file));

                                        //We need to sort the array as per $filesToScan
                                        $fileKey = array_search($fileName, $filesToScan);

                                        $filesToProcess[$fileKey] = $file;
                                    }
                                }

                                ksort($filesToProcess);

                                $file = null;

                                foreach ($filesToProcess as $file) {
                                    $table = new \XBase\TableReader(base_path($file));

                                    $counter = 0;
                                    while ($record = $table->nextRecord()) {
                                        $fileName = strtolower(str_replace($dir . '/', '', $file));

                                        if ($fileName === 'contract.dbf') {
                                            $data['contracts']['data'][$record->get('companycd') . '-' . $record->get('contractdt') . '-' . $record->get('custcode')] = $record->getData();
                                        } else if ($fileName === 'customer.dbf') {
                                            $data['customers']['data'][$record->get('custcode')] = $record->getData();
                                        } else if ($fileName === 'destn.dbf') {
                                            $data['destn']['data'][$counter] = $record->getData();
                                        } else if ($fileName === 'invhead.dbf') {
                                            if ($record->getData()['remark'] === 'CANCELLED') {
                                                continue;
                                            }

                                            $data['invoices']['data'][$record->get('companycd') . '-' . $record->get('invoicedt') . '-' . $record->get('custcode')]['invhead'] = $record->getData();
                                        } else if ($fileName === 'invdet.dbf') {
                                            if (isset($data['invoices']['data'][$record->get('companycd') . '-' . $record->get('invoicedt') . '-' . $record->get('custcode')]['invhead'])) {
                                                $data['invoices']['data'][$record->get('companycd') . '-' . $record->get('invoicedt') . '-' . $record->get('custcode')]['invdet'] = $record->getData();
                                            }
                                        } else if ($fileName === 'loadtype.dbf') {
                                            $data['loadtype']['data'][$counter] = $record->getData();
                                        } else if ($fileName === 'pay_recpt.dbf') {
                                            $data['pay_recpt']['data'][$record->get('companycd') . '-' . $record->get('recptdt') . '-' . $record->get('custcode')] = $record->getData();
                                        } else if ($fileName === 'uom.dbf') {
                                            $data['uom']['data'][$counter] = $record->getData();
                                        }

                                        $counter++;
                                    }

                                    if ($fileName === 'contract.dbf') {
                                        $data['contracts']['totalEntries'] = count($data['contracts']['data']);
                                    } else if ($fileName === 'customer.dbf') {
                                        $data['customers']['totalEntries'] = count($data['customers']['data']);
                                    } else if ($fileName === 'destn.dbf') {
                                        $data['destn']['totalEntries'] = count($data['destn']['data']);
                                    } else if ($fileName === 'invhead.dbf' || $fileName === 'invdet.dbf') {
                                        $data['invoices']['totalEntries'] = count($data['invoices']['data']);
                                    } else if ($fileName === 'loadtype.dbf') {
                                        $data['loadtype']['totalEntries'] = count($data['loadtype']['data']);
                                    } else if ($fileName === 'pay_recpt.dbf') {
                                        $data['pay_recpt']['totalEntries'] = count($data['pay_recpt']['data']);
                                    } else if ($fileName === 'uom.dbf') {
                                        $data['uom']['totalEntries'] = count($data['uom']['data']);
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\throwable $e) {
                throw $e;
            }

            if (count($data) > 0) {
                foreach ($data as $dataKey => $dataValue) {
                    $dataToWrite = [];
                    $dataToWrite[$dataKey]['totalEntries'] = count($dataValue);
                    $dataToWrite[$dataKey]['data'] = $dataValue;

                    $dataToWrite = $this->helper->encode($dataToWrite);

                    try {
                        $this->localContent->write($this->dataDir . 'BaggaCarriers/Extracted/' . $dataKey . '.json', $this->helper->encode($dataValue));
                    } catch (FilesystemException | UnableToWriteFile | \throwable $e) {
                        trace([$e]);
                    }
                }
            }
        } catch (\throwable $e) {
            trace([$e]);
        }
    }

    protected function importCompaniesData()
    {
        try {
            $companies = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/companies.json'), true);

            if (count($companies['data']) > 0) {
                foreach ($companies['data'] as $company) {
                    //Only import Bagga Carriers & Bagga Carriers P
                    if ($company['companycd'] != '02' && $company['companycd'] != '99') {
                        continue;
                    }

                    $newCompany['business_type'] = 'organisations';
                    $newCompany['reference'] = $company['companycd'];
                    $newCompany['name'] = $company['company'];
                    $newCompany['description'] = $company['compdesc'];
                    $newCompany['company_phone_1'] = $company['compstd'] . '-' . $company['compphone1'];
                    $newCompany['company_phone_2'] = $company['compphone2'];
                    $newCompany['company_fax'] = $company['compfax'];
                    $newCompany['company_website'] = '';
                    $newCompany['company_email'] = $company['compemail'];
                    if (trim($company['vatno']) === '') {
                        $newCompany['gst'] = '000000000000000';
                    }
                    $newCompany['gst_date'] = $company['vatdate'];
                    if (trim($company['panno']) === '') {
                        $company['panno'] = $this->helper->random(\Phalcon\Support\Helper\Str\Random::RANDOM_DISTINCT, 10);
                    }
                    $newCompany['pan'] = $company['panno'];
                    $newCompany['pan_date'] = $company['pandate'];
                    $newCompany['reg'] = $company['regno'];
                    $newCompany['reg_date'] = $company['regdate'];
                    $newCompany['archived'] = false;
                    $newCompany['address_ids'] = [
                        [
                            'new' => 1,
                            'address_reference' => $company['companycd'],
                            'street_address' => $company['compadd1'],
                            'street_address_2' => $company['compadd2'],
                            'street_address_3' => $company['compadd3']
                        ]
                    ];

                    $companiesPackage = new \Apps\Tms\Packages\Companies\Companies;

                    $companiesPackage->addCompany($newCompany);
                }
            }
        } catch (FilesystemException | UnableToReadFile | \throwable $e) {
            trace([$e]);
        }

        try {
            $customers = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/customers.json'), true);

            $errors = [];

            if (count($customers['data']) > 0) {
                foreach ($customers['data'] as $customerId => $customer) {
                    $newCompany = [];
                    $newCompany['business_type'] = 'customers';
                    $newCompany['reference'] = $customer['custcode'];
                    $newCompany['name'] = $customer['custname'];
                    $newCompany['description'] = '';
                    $newCompany['company_phone_1'] = $customer['custstd'] . '-' . $customer['custphone1'];
                    $newCompany['company_phone_2'] = $customer['custphone2'];
                    $newCompany['company_fax'] = $customer['custfax'];
                    $newCompany['company_website'] = '';
                    $newCompany['company_email'] = $customer['custemail'];
                    $newCompany['archived'] = false;
                    $newCompany['address_ids'] = [
                        [
                            'new' => 1,
                            'address_reference' => $customer['custcode'],
                            'attention_to' => $customer['custperson'],
                            'street_address' => $customer['custadd1'],
                            'street_address_2' => $customer['custadd2'],
                            'street_address_3' => $customer['custadd3']
                        ]
                    ];

                    $companiesPackage = new \Apps\Tms\Packages\Companies\Companies;

                    try {
                        $companiesPackage->addCompany($newCompany);
                    } catch (\throwable $e) {
                        preg_match('/\d{1,}/', $e->getMessage(), $currentID);

                        if (count($currentID) > 0) {
                            $newCompany['id'] = $currentID[0];
                            $newCompany['address_ids'] = [
                                [
                                    'new' => 1,
                                    'address_reference' => $customer['custcode'],
                                    'attention_to' => $customer['custperson'],
                                    'street_address' => $customer['custadd1'],
                                    'street_address_2' => $customer['custadd2'],
                                    'street_address_3' => $customer['custadd3']
                                ]
                            ];

                            $companiesPackage->updateCompany($newCompany);
                        }
                    } catch (\throwable $e) {
                        $errors[$customerId]['message'] = $e->getMessage();
                        $errors[$customerId]['newCompany'] = $newCompany;
                        $errors[$customerId]['customer'] = $customer;
                    }
                }
            }
        } catch (FilesystemException | UnableToReadFile | \throwable $e) {
            trace([$e]);
        }

        trace([$errors]);
    }

    protected function importUom()
    {
        try {
            $uoms = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/uom.json'), true);

            $errors = [];

            if (count($uoms['data']) > 0) {
                foreach ($uoms['data'] as $uomId => $uom) {
                    if ($uom['uom'] === '') {
                        continue;
                    }

                    $newUom = [];
                    $newUom['name'] = $uom['uom'];
                    $newUom['description'] = $uom['uom'];
                    $newUom['archived'] = false;

                    $uomPackage = new \Apps\Tms\Packages\Tools\Uom\ToolsUom;

                    try {
                        $uomPackage->addUom($newUom);
                    } catch (\throwable $e) {
                        $errors[$uomId]['message'] = $e->getMessage();
                        $errors[$uomId]['newUom'] = $newUom;
                    }
                }
            }
        } catch (FilesystemException | UnableToReadFile | \throwable $e) {
            trace([$e]);
        }
    }

    protected function importLt()
    {
        try {
            $uomPackage = new \Apps\Tms\Packages\Tools\Uom\ToolsUom;

            $kgsUom = $uomPackage->getUomByName('KGS');

            if (!$kgsUom) {
                throw new \Exception('Kgs Uom Not found!');
            }

            $loadTypes = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/loadtype.json'), true);

            $errors = [];

            if (count($loadTypes['data']) > 0) {
                foreach ($loadTypes['data'] as $ltId => $loadtype) {
                    if ($loadtype['loadtype'] === '') {
                        continue;
                    }

                    $newLoadType = [];
                    $newLoadType['name'] = $loadtype['loadtype'];
                    $newLoadType['description'] = $loadtype['loadtype'];
                    if (!$loadtype['capacity']) {
                        $loadtype['capacity'] = 0;
                    }
                    $newLoadType['capacity'] = $loadtype['capacity'];
                    $newLoadType['uom'] = $kgsUom['id'];
                    $newLoadType['archived'] = false;

                    $ltPackage = new \Apps\Tms\Packages\System\Tools\Lt\SystemToolsLt;

                    try {
                        $ltPackage->addLt($newLoadType);
                    } catch (\throwable $e) {
                        $errors[$ltId]['message'] = $e->getMessage();
                        $errors[$ltId]['newLoadType'] = $newLoadType;
                    }
                }
            }
        } catch (FilesystemException | UnableToReadFile | \throwable $e) {
            trace([$e]);
        }
    }

    protected function importAddressReferencesFromInvoices()
    {
        $errors = [];

        try {
            $invoices = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/invoices.json'), true);

            $companiesPackage = new \Apps\Tms\Packages\Companies\Companies;

            if (count($invoices['data']) > 0) {
                foreach ($invoices['data'] as $invoiceId => $invoice) {
                    if (!isset($invoice['invdet'])) {
                        continue;
                    }

                    //Only import Bagga Carriers & Bagga Carriers P
                    if ($invoice['invdet']['companycd'] != '02' && $invoice['invdet']['companycd'] != '99') {
                        continue;
                    }

                    if ($invoice['invdet']['custcode'] === '') {
                        continue;
                    }

                    if ($invoice['invdet']['fromdest'] === '' && $invoice['invdet']['todest'] === '') {
                        continue;
                    }

                    if ($invoice['invdet']['fromdest'] === '' && $invoice['invdet']['todest'] !== '') {
                        $invoice['invdet']['fromdest'] = 'TAKEN TO INFO: ' . $invoice['invdet']['todest'];
                    }
                    if ($invoice['invdet']['todest'] === '' && $invoice['invdet']['fromdest'] !== '') {
                        $invoice['invdet']['todest'] = 'TAKEN FROM INFO: ' . $invoice['invdet']['fromdest'];
                    }

                    $company = $companiesPackage->getCompanyByReference($invoice['invdet']['custcode']);

                    if (!$company) {
                        throw new \Exception('Company with reference: ' . $invoice['invdet']['custcode'] . ' not found!');
                    }

                    $addresses = [];
                    if (isset($company['addresses'])) {
                        $addresses = $company['addresses'];
                    }

                    $addressesReferences = [];

                    if (count($addresses) > 0) {
                        foreach ($addresses as $address) {
                            array_push($addressesReferences, $address['address_reference']);
                        }
                    }

                    $newAddresses = [];
                    if (!in_array($invoice['invdet']['fromdest'], $addressesReferences)) {
                        array_push($newAddresses,
                            [
                                'new' => 1,
                                'address_reference' => $invoice['invdet']['fromdest'],
                                'attention_to' => null,
                                'street_address' => null,
                                'street_address_2' => null,
                                'street_address_3' => null
                            ]
                        );
                    }
                    if (!in_array($invoice['invdet']['todest'], $addressesReferences)) {
                        array_push($newAddresses,
                            [
                                'new' => 1,
                                'address_reference' => $invoice['invdet']['todest'],
                                'attention_to' => null,
                                'street_address' => null,
                                'street_address_2' => null,
                                'street_address_3' => null
                            ]
                        );
                    }

                    if (count($newAddresses) > 0) {
                        $company['address_ids'] = $newAddresses;

                        $companiesPackage->updateCompany($company);
                    }
                }
            }
        } catch (FilesystemException | UnableToReadFile | \throwable $e) {
            $errors[$invoiceId]['message'] = $e->getMessage();
            $errors[$invoiceId]['company'] = $company;
            $errors[$invoiceId]['invoice'] = $invoice;
        }

        trace([$errors]);
    }

    protected function importVehicleFromInvoices()
    {
        $errors = [];

        try {
            $invoices = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/invoices.json'), true);

            $vehiclesPackage = new \Apps\Tms\Packages\Vehicles\Vehicles;

            if (count($invoices['data']) > 0) {
                foreach ($invoices['data'] as $invoiceId => $invoice) {
                    if (!isset($invoice['invdet'])) {
                        continue;
                    }

                    //Only import Bagga Carriers & Bagga Carriers P
                    if ($invoice['invdet']['companycd'] != '02' && $invoice['invdet']['companycd'] != '99') {
                        continue;
                    }

                    if ($invoice['invdet']['custcode'] === '') {
                        continue;
                    }

                    if ($invoice['invdet']['vehicleno'] === '') {
                        continue;
                    }

                    $vehicle = $vehiclesPackage->getVehicleByRegistrationNo($invoice['invdet']['vehicleno']);

                    if (!$vehicle) {
                        $vehiclesPackage->addVehicle([
                            'organisation_id'   => 2,
                            'registration_no'   => $invoice['invdet']['vehicleno'],
                            'status'            => 0,
                            'archived'          => false
                        ]);
                    }
                }
            }
        } catch (FilesystemException | UnableToReadFile | \throwable $e) {
            $errors[$invoiceId]['message'] = $e->getMessage();
            $errors[$invoiceId]['vehicle'] = $vehicle;
            $errors[$invoiceId]['invoice'] = $invoice;
        }

        trace([$errors]);
    }

    protected function importLrFromInvoices()
    {
        $errors = [];

        $uomPackage = new \Apps\Tms\Packages\Tools\Uom\ToolsUom;

        try {
            $invoices = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/invoices.json'), true);

            if (count($invoices['data']) > 0) {
                $counters = [];
                $counters['total_start'] = 0;
                $counters['total_start_ids'] = [];
                $counters['total_end'] = 0;
                $counters['total_end_ids'] = [];
                $counters['invalid_invdet'] = 0;
                $counters['invalid_invdet_ids'] = [];
                $counters['invalid_invhead'] = 0;
                $counters['invalid_invhead_ids'] = [];
                $counters['cancelled_remark'] = 0;
                $counters['cancelled_remark_ids'] = [];
                $counters['no_lr_no'] = 0;
                $counters['no_lr_no_ids'] = [];
                $counters['no_lr_dt'] = 0;
                $counters['no_lr_dt_ids'] = [];
                $counters['custcode_missing'] = 0;
                $counters['custcode_missing_ids'] = [];
                $counters['companycode_missing'] = 0;
                $counters['companycode_missing_ids'] = [];
                $counters['companycode_incorrect'] = 0;
                $counters['companycode_incorrect_ids'] = [];
                $counters['date_error'] = 0;
                $counters['date_error_ids'] = [];
                $counters['duplicate_lrs'] = 0;
                $counters['duplicate_lrs_ids'] = [];
                $counters['first_exception'] = 0;
                $counters['first_exception_ids'] = [];
                $counters['second_exception'] = 0;
                $counters['second_exception_ids'] = [];
                $counters['incorrect_invoice_no'] = 0;

                $counters['incorrect_invoice_no_ids'] = [];

                foreach ($invoices['data'] as $invoiceId => $invoice) {
                    array_push($counters['total_start_ids'], $invoiceId);
                    $counters['total_start']++;

                    $lrsPackage = new \Apps\Tms\Packages\Jobs\Lrs\JobsLrs;
                    $toolsChargesPackage = new \Apps\Tms\Packages\Tools\Charges\ToolsCharges;
                    $vehiclesPackage = new \Apps\Tms\Packages\Vehicles\Vehicles;
                    $companiesPackage = new \Apps\Tms\Packages\Companies\Companies;

                    if (!isset($invoice['invdet']['companycd']) || $invoice['invdet']['companycd'] === '') {
                        array_push($counters['companycode_missing_ids'], $invoiceId);
                        $counters['companycode_missing']++;
                        continue;
                    }

                    //Only import Bagga Carriers & Bagga Carriers P
                    if ($invoice['invdet']['companycd'] != '02' && $invoice['invdet']['companycd'] != '99') {
                        array_push($counters['companycode_incorrect_ids'], $invoiceId);
                        $counters['companycode_incorrect']++;
                        continue;
                    }

                    if (!isset($invoice['invdet'])) {
                        array_push($counters['invalid_invdet_ids'], $invoiceId);
                        $counters['invalid_invdet']++;
                        continue;
                    }

                    if (!isset($invoice['invhead'])) {
                        array_push($counters['invalid_invhead_ids'], $invoiceId);
                        $counters['invalid_invhead']++;
                        continue;
                    }

                    if (isset($invoice['invhead']['remark']) && $invoice['invhead']['remark'] === 'CANCELLED') {
                        array_push($counters['cancelled_remark_ids'], $invoiceId);
                        $counters['cancelled_remark']++;
                        continue;
                    }

                    if (!isset($invoice['invdet']['lrno']) || $invoice['invdet']['lrno'] === 0) {
                        array_push($counters['no_lr_no_ids'], $invoiceId);
                        $counters['no_lr_no']++;
                        continue;
                    }

                    if (!isset($invoice['invdet']['invoiceno']) || $invoice['invdet']['invoiceno'] === 0) {
                        $invoice['invdet']['invoiceno'] = time() . random_int(1000, 9999);
                    }

                    if (!isset($invoice['invdet']['lrdt']) || $invoice['invdet']['lrdt'] === '') {
                        if (isset($invoice['invdet']['invoicedt'])) {
                            $invoice['invdet']['lrdt'] = $invoice['invdet']['invoicedt'];
                        } else {
                            array_push($counters['no_lr_dt_ids'], $invoiceId);
                            $counters['no_lr_dt']++;
                            continue;
                        }
                    }

                    if (!isset($invoice['invdet']['custcode']) || $invoice['invdet']['custcode'] === '') {
                        array_push($counters['custcode_missing_ids'], $invoiceId);
                        $counters['custcode_missing']++;
                        continue;
                    }

                    try {
                        $date = \Carbon\Carbon::Parse($invoice['invdet']['lrdt']);
                        $now = \Carbon\Carbon::now();

                        if ($now->month < 4) {
                            $nowEndYear = substr($now->year, 2);
                            $nowStartYear = substr($now->clone()->subYear(1)->year, 2);
                        } else {
                            $nowStartYear = substr($now->year, 2);
                            $nowEndYear = substr($now->clone()->addYear(1)->year, 2);
                        }

                        if ($date->year > 2000) {
                            if ($date->month < 4) {
                                $endYear = substr($date->year, 2);
                                $startYear = substr($date->clone()->subYear(1)->year, 2);
                            } else {
                                $startYear = substr($date->year, 2);
                                $endYear = substr($date->clone()->addYear(1)->year, 2);
                            }
                        } else {
                            if ($date->month < 4) {
                                $endYear = $date->year;
                                $startYear = $date->clone()->subYear(1)->year;
                            } else {
                                $startYear = $date->year;
                                $endYear = $date->clone()->addYear(1)->year;
                            }
                        }
                    } catch (\throwable $e) {
                        array_push($counters['date_error_ids'], $invoiceId);
                        $counters['date_error']++;
                        continue;
                    }

                    $lr = $lrsPackage->getById($invoice['invdet']['lrno']);

                    $vehicle = $vehiclesPackage->getVehicleByRegistrationNo($invoice['invdet']['vehicleno']);

                    if (!$lr) {
                        try {
                            $archived = false;
                            if ($startYear . '-' . $endYear !== $nowStartYear . '-' . $nowEndYear) {
                                $archived = true;
                            }

                            $organisation = $companiesPackage->getFirst(by: 'reference', value: $invoice['invdet']['companycd'], returnArray: true);
                            if (!$organisation) {
                                $organisation['id'] = (int) $invoice['invdet']['companycd'];
                            }
                            $company = $companiesPackage->getFirst(by: 'reference', value: $invoice['invdet']['custcode'], returnArray: true);

                            if (!$company) {
                                $company['id'] = (int) $invoice['invdet']['custcode'];
                            }

                            $company = $companiesPackage->getCompany($company['id']);

                            $lrFirstArr =
                                [
                                    'lr_no'                     => $invoice['invdet']['lrno'],
                                    'financial_year'            => $startYear . '-' . $endYear,
                                    'invoice_no'                => $invoice['invdet']['invoiceno'],
                                    'organisation_id'           => $organisation['id'],
                                    'company_id'                => $company['id'],
                                    'from_company_id'           => $company['id'],
                                    'to_company_id'             => $company['id'],
                                    'from_company_address_id'   => $company['id'],
                                    'to_company_address_id'     => $company['id'],
                                    'vehicle_id'                => ($vehicle && isset($vehicle['id'])) ? $vehicle['id'] : 0,
                                    'date'                      => $date->format('d-m-Y'),
                                    'status'                    => 4,
                                    'archived'                  => $archived
                                ];

                            $lrsPackage->addLr($lrFirstArr, true);

                            $lr = $lrsPackage->packagesData->last;
                        } catch (\throwable $e) {
                            if (str_contains($e->getMessage(), 'invoice_no')) {
                                preg_match('/\d{1,}/', $e->getMessage(), $invoiceID);

                                if (count($invoiceID) > 0) {
                                    $invoice['invdet']['invoiceno'] = time() . random_int(1000, 9999);

                                    $lrsPackage = new \Apps\Tms\Packages\Jobs\Lrs\JobsLrs;

                                    try {
                                        $lrSecondArr =
                                            [
                                                'lr_no'                     => $invoice['invdet']['lrno'],
                                                'financial_year'            => $startYear . '-' . $endYear,
                                                'invoice_no'                => $invoice['invdet']['invoiceno'],
                                                'organisation_id'           => $organisation['id'],
                                                'company_id'                => $company['id'],
                                                'vehicle_id'                => ($vehicle && isset($vehicle['id'])) ? $vehicle['id'] : 0,
                                                'date'                      => $date->format('d-m-Y'),
                                                'status'                    => 4,
                                                'archived'                  => $archived,
                                                'invoice_dev_notes'         => 'Invoice duplicate of LR: ' . $invoiceID[0] . '. Changed the invoice# to timestamp.'
                                            ];

                                        $lrsPackage->addLr($lrSecondArr, true);

                                        $lr = $lrsPackage->packagesData->last;

                                        array_push($counters['incorrect_invoice_no_ids'], $invoiceId);
                                        $counters['incorrect_invoice_no']++;
                                    } catch (\throwable $e) {
                                        $counters['second_exception_ids'][$invoiceId] = $e->getMessage();
                                        $counters['second_exception']++;
                                        continue;
                                    }
                                }
                            } else {
                                $counters['first_exception_ids'][$invoiceId] = $e->getMessage();
                                $counters['first_exception']++;
                                continue;
                            }
                        }
                    } else {
                        array_push($counters['duplicate_lrs_ids'], $invoiceId);
                        $counters['duplicate_lrs']++;
                        continue;
                    }

                    //Add Charges
                    $charges = [];
                    //First we check if all UoMs are in the system.
                    if (isset($invoice['invdet']['uom']) && $invoice['invdet']['uom'] !== '') {
                        $quantityUom = $uomPackage->getUomByName($invoice['invdet']['uom']);

                        if (!$quantityUom) {
                            $newUom = [];
                            $newUom['name'] = $invoice['invdet']['uom'];
                            $newUom['description'] = $invoice['invdet']['uom'];
                            $newUom['archived'] = false;

                            try {
                                $uomPackage->addUom($newUom);

                                $quantityUom = $uomPackage->packagesData->last;
                            } catch (\throwable $e) {
                                $errors[$invoiceId]['uomErrorMessage'] = $e->getMessage();
                                $errors[$invoiceId]['newUom'] = $newUom;
                            }
                        }
                    }
                    if (isset($invoice['invdet']['rateper']) && $invoice['invdet']['rateper'] !== '') {
                        $rateUom = $uomPackage->getUomByName($invoice['invdet']['rateper']);

                        if (!$rateUom) {
                            $newUom = [];
                            $newUom['name'] = $invoice['invdet']['rateper'];
                            $newUom['description'] = $invoice['invdet']['rateper'];
                            $newUom['archived'] = false;

                            try {
                                $uomPackage->addUom($newUom);

                                $rateUom = $uomPackage->packagesData->last;
                            } catch (\throwable $e) {
                                $errors[$invoiceId]['uomErrorMessage'] = $e->getMessage();
                                $errors[$invoiceId]['newUom'] = $newUom;
                            }
                        }
                    }

                    //Now we check if we have ToolCharges (Basically product name and charges name that all Lrs and invoices will use)
                    //First we check the product charges
                    if (isset($invoice['invdet']['product']) && $invoice['invdet']['product'] !== '') {
                        $chargeProduct = $toolsChargesPackage->getChargeByName($invoice['invdet']['product']);

                        if (!$chargeProduct) {
                            $newCharge = [];
                            $newCharge['name'] = $invoice['invdet']['product'];
                            $newCharge['type'] = 1;
                            $newCharge['description'] = $invoice['invdet']['product'];
                            $newCharge['archived'] = false;

                            try {
                                $toolsChargesPackage->addCharge($newCharge);

                                $chargeProduct = $toolsChargesPackage->packagesData->last;
                            } catch (\throwable $e) {
                                $errors[$invoiceId]['chargeErrorMessage'] = $e->getMessage();
                                $errors[$invoiceId]['chargeProduct'] = $newCharge;
                            }
                        }

                        $charges['product']['lr_no'] = $lr['id'];
                        $charges['product']['charge_id'] = $chargeProduct['id'];
                        $charges['product']['quantity'] = $invoice['invdet']['quantity'];
                        $charges['product']['quantity_uom_id'] = $quantityUom['id'];
                        $charges['product']['rate'] = $invoice['invdet']['rate'];
                        $charges['product']['rate_uom_id'] = $rateUom['id'];
                        $charges['product']['amount'] = $invoice['invdet']['amount'];

                        //Now we add other charges (Detention Charges)
                        $chargeDetention = $toolsChargesPackage->getChargeByName('DETENTION');

                        if (!$chargeDetention) {
                            $newCharge = [];
                            $newCharge['name'] = 'DETENTION';
                            $newCharge['type'] = 2;
                            $newCharge['description'] = 'DETENTION';
                            $newCharge['archived'] = false;

                            try {
                                $toolsChargesPackage->addCharge($newCharge);

                                $chargeDetention = $toolsChargesPackage->packagesData->last;
                            } catch (\throwable $e) {
                                $errors[$invoiceId]['chargeErrorMessage'] = $e->getMessage();
                                $errors[$invoiceId]['chargeDetention'] = $newCharge;
                            }
                        }

                        if ($invoice['invdet']['detendays'] && $invoice['invdet']['detenrate'] && $invoice['invdet']['detenamt']) {
                            $charges['detention']['lr_no'] = $lr['id'];
                            $charges['detention']['charge_id'] = $chargeDetention['id'];
                            $charges['detention']['quantity'] = $invoice['invdet']['detendays'];
                            $charges['detention']['rate'] = $invoice['invdet']['detenrate'];
                            $charges['detention']['amount'] = $invoice['invdet']['detenamt'];
                        }

                        //Now we add other charges (Hamali Charges)
                        $chargeHamali = $toolsChargesPackage->getChargeByName('HAMALI');

                        if (!$chargeHamali) {
                            $newCharge = [];
                            $newCharge['name'] = 'HAMALI';
                            $newCharge['type'] = 2;
                            $newCharge['description'] = 'HAMALI';
                            $newCharge['archived'] = false;

                            try {
                                $toolsChargesPackage->addCharge($newCharge);

                                $chargeHamali = $toolsChargesPackage->packagesData->last;
                            } catch (\throwable $e) {
                                $errors[$invoiceId]['chargeErrorMessage'] = $e->getMessage();
                                $errors[$invoiceId]['chargeHamali'] = $newCharge;
                            }
                        }

                        if ($invoice['invdet']['hamali']) {
                            $charges['hamali']['lr_no'] = $lr['id'];
                            $charges['hamali']['charge_id'] = $chargeHamali['id'];
                            $charges['hamali']['amount'] = $invoice['invdet']['hamali'];
                        }

                        //Now we add other charges (Misc 1 Charges)
                        if (isset($invoice['invdet']['miscdesc1']) && $invoice['invdet']['miscdesc1'] !== '' && $invoice['invdet']['miscamt1']) {
                            $chargeMisc1 = $toolsChargesPackage->getChargeByName($invoice['invdet']['miscdesc1']);

                            if (!$chargeMisc1) {
                                $newCharge = [];
                                $newCharge['name'] = $invoice['invdet']['miscdesc1'];
                                $newCharge['type'] = 2;
                                $newCharge['description'] = $invoice['invdet']['miscdesc1'];
                                $newCharge['archived'] = false;

                                try {
                                    $toolsChargesPackage->addCharge($newCharge);

                                    $chargeMisc1 = $toolsChargesPackage->packagesData->last;
                                } catch (\throwable $e) {
                                    $errors[$invoiceId]['chargeErrorMessage'] = $e->getMessage();
                                    $errors[$invoiceId]['chargeMisc1'] = $newCharge;
                                }
                            }

                            $charges['miscdesc1']['lr_no'] = $lr['id'];
                            $charges['miscdesc1']['charge_id'] = $chargeMisc1['id'];
                            $charges['miscdesc1']['amount'] = $invoice['invdet']['miscamt1'];
                        }
                        //Now we add other charges (Misc 2 Charges)
                        if (isset($invoice['invdet']['miscdesc2']) && $invoice['invdet']['miscdesc2'] !== '' && $invoice['invdet']['miscamt2']) {
                            $chargeMisc2 = $toolsChargesPackage->getChargeByName($invoice['invdet']['miscdesc2']);

                            if (!$chargeMisc2) {
                                $newCharge = [];
                                $newCharge['name'] = $invoice['invdet']['miscdesc2'];
                                $newCharge['type'] = 2;
                                $newCharge['description'] = $invoice['invdet']['miscdesc2'];
                                $newCharge['archived'] = false;

                                try {
                                    $toolsChargesPackage->addCharge($newCharge);

                                    $chargeMisc2 = $toolsChargesPackage->packagesData->last;
                                } catch (\throwable $e) {
                                    $errors[$invoiceId]['chargeErrorMessage'] = $e->getMessage();
                                    $errors[$invoiceId]['chargeMisc2'] = $newCharge;
                                }
                            }

                            $charges['miscdesc2']['lr_no'] = $lr['id'];
                            $charges['miscdesc2']['charge_id'] = $chargeMisc2['id'];
                            $charges['miscdesc2']['amount'] = $invoice['invdet']['miscamt2'];
                        }
                        //Now we add other charges (Misc 2 Charges)
                        if (isset($invoice['invdet']['miscdesc3']) && $invoice['invdet']['miscdesc3'] !== '' && $invoice['invdet']['miscamt3']) {
                            $chargeMisc3 = $toolsChargesPackage->getChargeByName($invoice['invdet']['miscdesc3']);

                            if (!$chargeMisc3) {
                                $newCharge = [];
                                $newCharge['name'] = $invoice['invdet']['miscdesc3'];
                                $newCharge['type'] = 2;
                                $newCharge['description'] = $invoice['invdet']['miscdesc3'];
                                $newCharge['archived'] = false;

                                try {
                                    $toolsChargesPackage->addCharge($newCharge);

                                    $chargeMisc3 = $toolsChargesPackage->packagesData->last;
                                } catch (\throwable $e) {
                                    $errors[$invoiceId]['chargeErrorMessage'] = $e->getMessage();
                                    $errors[$invoiceId]['chargeMisc3'] = $newCharge;
                                }
                            }

                            $charges['miscdesc3']['lr_no'] = $lr['id'];
                            $charges['miscdesc3']['charge_id'] = $chargeMisc3['id'];
                            $charges['miscdesc3']['amount'] = $invoice['invdet']['miscamt3'];
                        }
                    }

                    if (count($charges) > 0) {
                        foreach ($charges as $chargeType => $charge) {
                            $jobsChargesPackage = new \Apps\Tms\Packages\Jobs\Charges\JobsCharges;

                            $lrCharge = $jobsChargesPackage->getJobsChargesByLrnoAndChargesId($charge['lr_no'], $charge['charge_id']);

                            if (!$lrCharge) {
                                try {
                                    if ($chargeType === 'product') {
                                        $charge['visibility'] = 3;
                                    } else {
                                        $charge['visibility'] = 2;
                                    }

                                    $jobsChargesPackage->add($charge);
                                } catch (\throwable $e) {
                                    $errors[$invoiceId]['jobChargeErrorMessage'] = $e->getMessage();
                                    $errors[$invoiceId]['jobChargeType'] = $charge;
                                }
                            }
                        }
                        // if ($lr['id'] === 3085) {
                        //     trace([$charges, $errors]);
                        // }
                    }

                    array_push($counters['total_end_ids'], $invoiceId);
                    $counters['total_end']++;
                }
            }
        } catch (FilesystemException | UnableToReadFile | \throwable $e) {
            trace([$e]);
            $errors[$invoiceId]['message'] = $e->getMessage();
            $errors[$invoiceId]['lr'] = $lr;
            $errors[$invoiceId]['invoice'] = $invoice;
        }

        $lrsPackage = new \Apps\Tms\Packages\Jobs\Lrs\JobsLrs;
        $lrsPackage->getDbCount(true);
        $tripsPacage = new \Apps\Tms\Packages\Jobs\Trips\JobsTrips;
        $tripsPacage->getDbCount(true);
        $invoicesPacage = new \Apps\Tms\Packages\Jobs\Invoices\JobsInvoices;
        $invoicesPacage->getDbCount(true);
        $jobsChargesPackage = new \Apps\Tms\Packages\Jobs\Charges\JobsCharges;
        $jobsChargesPackage->getDbCount(true);

        try {
            $this->localContent->write($this->dataDir . 'BaggaCarriers/Extracted/invoice_errors.json', $this->helper->encode(['counters' => $counters, 'errors' => $errors]));
        } catch (FilesystemException | UnableToWriteFile | \throwable $e) {
            trace([$e]);
        }

        trace([$errors]);
    }
}