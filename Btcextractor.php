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
        //Extract Data from DBF Files
        // try {
        //     //Extract Company Data
        //     $data = [];

        //     $table = new \XBase\TableReader(base_path($this->dataDir . 'BaggaCarriers/BILL/DATA/COMPANY.DBF'));

        //     while ($record = $table->nextRecord()) {
        //         $data['companies']['data'][$record->get('companycd')] = $record->getData();
        //     }
        //     $data['companies']['totalEntries'] = count($data['companies']['data']);

        //     $filesToScan = ['contract.dbf','customer.dbf','invdet.dbf','uom.dbf','destn.dbf','invhead.dbf','loadtype.dbf','pay_recpt.dbf'];

        //     try {
        //         $dirs = $this->basepackages->utils->scanDir($this->dataDir . 'BaggaCarriers/BILL/DATA/');

        //         sort($dirs['dirs']);

        //         if (count($dirs['dirs']) > 0) {
        //             foreach ($dirs['dirs'] as $dirKey => $dir) {
        //                 if (!str_ends_with($dir, 'STRUE')) {
        //                     $files = $this->basepackages->utils->scanDir($dir, false, ['.BAK','.CDX','.FPT']);

        //                     sort($files['files']);

        //                     if (count($files['files']) > 0) {
        //                         foreach ($files['files'] as $file) {
        //                             if (str_ends_with($file, '.DBF')) {
        //                                 $fileName = strtolower(str_replace($dir . '/', '', $file));

        //                                 if (in_array($fileName, $filesToScan)) {
        //                                     $table = new \XBase\TableReader(base_path($file));

        //                                     $counter = 0;
        //                                     while ($record = $table->nextRecord()) {
        //                                         if ($fileName === 'contract.dbf') {
        //                                             $data['contracts']['data'][$record->get('companycd') . '-' . $record->get('contractdt') . '-' . $record->get('custcode')] = $record->getData();
        //                                         } else if ($fileName === 'customer.dbf') {
        //                                             $data['customers']['data'][$record->get('custcode')] = $record->getData();
        //                                         } else if ($fileName === 'destn.dbf') {
        //                                             $data['destn']['data'][$counter] = $record->getData();
        //                                         } else if ($fileName === 'invhead.dbf') {
        //                                             $data['invoices']['data'][$record->get('companycd') . '-' . $record->get('invoicedt') . '-' . $record->get('custcode')]['invhead'] = $record->getData();
        //                                         } else if ($fileName === 'invdet.dbf') {
        //                                             $data['invoices']['data'][$record->get('companycd') . '-' . $record->get('invoicedt') . '-' . $record->get('custcode')]['invdet'] = $record->getData();
        //                                         } else if ($fileName === 'loadtype.dbf') {
        //                                             $data['loadtype']['data'][$counter] = $record->getData();
        //                                         } else if ($fileName === 'pay_recpt.dbf') {
        //                                             $data['pay_recpt']['data'][$record->get('companycd') . '-' . $record->get('recptdt') . '-' . $record->get('custcode')] = $record->getData();
        //                                         } else if ($fileName === 'uom.dbf') {
        //                                             $data['uom']['data'][$counter] = $record->getData();
        //                                         }

        //                                         $counter++;
        //                                     }

        //                                     if ($fileName === 'contract.dbf') {
        //                                         $data['contracts']['totalEntries'] = count($data['contracts']['data']);
        //                                     } else if ($fileName === 'customer.dbf') {
        //                                         $data['customers']['totalEntries'] = count($data['customers']['data']);
        //                                     } else if ($fileName === 'destn.dbf') {
        //                                         $data['destn']['totalEntries'] = count($data['destn']['data']);
        //                                     } else if ($fileName === 'invhead.dbf' || $fileName === 'invdet.dbf') {
        //                                         $data['invoices']['totalEntries'] = count($data['invoices']['data']);
        //                                     } else if ($fileName === 'loadtype.dbf') {
        //                                         $data['loadtype']['totalEntries'] = count($data['loadtype']['data']);
        //                                     } else if ($fileName === 'pay_recpt.dbf') {
        //                                         $data['pay_recpt']['totalEntries'] = count($data['pay_recpt']['data']);
        //                                     } else if ($fileName === 'uom.dbf') {
        //                                         $data['uom']['totalEntries'] = count($data['uom']['data']);
        //                                     }
        //                                 }
        //                             }
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     } catch (\throwable $e) {
        //         throw $e;
        //     }

        //     if (count($data) > 0) {
        //         foreach ($data as $dataKey => $dataValue) {
        //             // $dataToWrite = [];
        //             // $dataToWrite[$dataKey]['totalEntries'] = count($dataValue);
        //             // $dataToWrite[$dataKey]['data'] = $dataValue;

        //             // $dataToWrite = $this->helper->encode($dataToWrite);

        //             try {
        //                 $this->localContent->write($this->dataDir . 'BaggaCarriers/Extracted/' . $dataKey . '.json', $this->helper->encode($dataValue));
        //             } catch (FilesystemException | UnableToWriteFile | \throwable $e) {
        //                 trace([$e]);
        //             }
        //         }
        //     }
        // } catch (\throwable $e) {
        //     trace([$e]);
        // }
        // trace(['done']);

        //Import Data to DB
        //Read Companies and Customers files.
        // try {
        //     $companies = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/companies.json'), true);

        //     if (count($companies['data']) > 0) {
        //         foreach ($companies['data'] as $company) {
        //             $newCompany['business_type'] = 'organisations';
        //             $newCompany['name'] = $company['company'];
        //             $newCompany['description'] = $company['compdesc'];
        //             $newCompany['company_phone_1'] = $company['compstd'] . '-' . $company['compphone1'];
        //             $newCompany['company_phone_2'] = $company['compphone2'];
        //             $newCompany['company_fax'] = $company['compfax'];
        //             $newCompany['company_website'] = '';
        //             $newCompany['company_email'] = $company['compemail'];
        //             $newCompany['gst'] = $company['vatno'];
        //             $newCompany['gst_date'] = $company['vatdate'];
        //             $newCompany['pan'] = $company['panno'];
        //             $newCompany['pan_date'] = $company['pandate'];
        //             $newCompany['reg'] = $company['regno'];
        //             $newCompany['reg_date'] = $company['regdate'];
        //             $newCompany['address_ids'] = [
        //                 [
        //                     'new' => 1,
        //                     'address_reference' => $company['companycd'],
        //                     'street_address' => $company['compadd1'],
        //                     'street_address_2' => $company['compadd2'],
        //                     'street_address_3' => $company['compadd3']
        //                 ]
        //             ];

        //             $companiesPackage = new \Apps\Tms\Packages\Companies\Companies;

        //             $companiesPackage->addCompany($newCompany);
        //         }
        //     }
        // } catch (FilesystemException | UnableToReadFile | \throwable $e) {
        //     trace([$e]);
        // }

        // try {
        //     $customers = $this->helper->decode($this->localContent->read($this->dataDir . 'BaggaCarriers/Extracted/customers.json'), true);

        //     $errors = [];

        //     if (count($customers['data']) > 0) {
        //         foreach ($customers['data'] as $customerId => $customer) {
        //             $newCompany = [];
        //             $newCompany['business_type'] = 'customers';
        //             $newCompany['name'] = $customer['custname'];
        //             $newCompany['description'] = '';
        //             $newCompany['company_phone_1'] = $customer['custstd'] . '-' . $customer['custphone1'];
        //             $newCompany['company_phone_2'] = $customer['custphone2'];
        //             $newCompany['company_fax'] = $customer['custfax'];
        //             $newCompany['company_website'] = '';
        //             $newCompany['company_email'] = $customer['custemail'];
        //             $newCompany['address_ids'] = [
        //                 [
        //                     'new' => 1,
        //                     'address_reference' => $customer['custcode'],
        //                     'attention_to' => $customer['custperson'],
        //                     'street_address' => $customer['custadd1'],
        //                     'street_address_2' => $customer['custadd2'],
        //                     'street_address_3' => $customer['custadd3']
        //                 ]
        //             ];

        //             $companiesPackage = new \Apps\Tms\Packages\Companies\Companies;

        //             try {
        //                 $companiesPackage->addCompany($newCompany);
        //             } catch (\throwable $e) {
        //                 preg_match('/\d{1,}/', $e->getMessage(), $currentID);

        //                 if (count($currentID) > 0) {
        //                     $newCompany['id'] = $currentID[0];
        //                     $newCompany['address_ids'] = [
        //                         [
        //                             'new' => 1,
        //                             'address_reference' => $customer['custcode'],
        //                             'attention_to' => $customer['custperson'],
        //                             'street_address' => $customer['custadd1'],
        //                             'street_address_2' => $customer['custadd2'],
        //                             'street_address_3' => $customer['custadd3']
        //                         ]
        //                     ];

        //                     $companiesPackage->updateCompany($newCompany);
        //                 }
        //             } catch (\throwable $e) {
        //                 $errors[$customerId]['message'] = $e->getMessage();
        //                 $errors[$customerId]['newCompany'] = $newCompany;
        //                 $errors[$customerId]['customer'] = $customer;
        //             }
        //         }
        //     }
        // } catch (FilesystemException | UnableToReadFile | \throwable $e) {
        //     trace([$e]);
        // }

        // trace([$errors]);
    }
}