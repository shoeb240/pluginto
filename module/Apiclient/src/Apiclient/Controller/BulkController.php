<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;

error_reporting(0);

class BulkController extends ApiclientActionController
{
    private $_error_msg = '';
    private $_error_code = '';
    private $_success_msg = '';
    
    //private $_csv_file_path = '/public/bulk/accounting_ledger.csv';
    private $_arr_csv_header_fields = array();
    private $_arr_csv_data = array();
    private $_total_rows = 0;
    private $_field_separate_char = ";";
    
    //private $_xml_file_name = '/public/bulk/xml.xml';
    private $_xml_fields = array();
    private $_xml_accounts = array();
    
    private $_default_customer_name = 'Default Customer';
    private $_default_supplier_name = 'Default Supplier';
    private $_default_account_financial_name = 'Default Financial Account';
    private $_default_bank_account_name = 'Default Bank Account';
    private $_default_account_name = 'Default Expense Account';
    private $_default_item_name = 'Default Item';
    //private $_field_enclose_char  = "\"";
    //private $_field_escape_char   = "\\";
    private $alreadyCreadedArr = array();
    
    
    //default settings
    public $destination = '';
    public $csvFileName;
    public $xmlFileName;
    public $maxSize = '104857600'; // bytes (1048576 bytes = 1 meg, 100MB)
    public $allowedExtensions = array('application/ms-excel', 'text/plain', 'application/xml', 'text/xml'); // mime types
    public $_upload_error = '';

    public function indexAction()
    {
        $params = array();
        
        return new ViewModel($params);
    }

    public function addAction()
    {
        $params = array();
        
        if ($this->request->isPost()) {
            
            $startRow = $this->getRequest()->getPost('start_row', 1);
            $endRow = $this->getRequest()->getPost('end_row', '');
            
            // upload
            $errors = '';
            
            $this->setDestination($_SERVER['DOCUMENT_ROOT'] . '/public/bulk/');
            $this->setAllowedExtensions(array('application/ms-excel', 'text/plain'));
            $this->setCsvFileName('transaction_' . $this->getUserId() . '.csv');
            $this->upload($_FILES['csv_file'], 'csv');
            $errors .= $this->getUploadError();

            $this->setDestination($_SERVER['DOCUMENT_ROOT'] . '/public/bulk/');
            $this->setAllowedExtensions(array('application/xml', 'text/xml'));
            $this->setXmlFileName('map_' . $this->getUserId() . '.xml');
            $this->upload($_FILES['xml_file'], 'xml');
            $errors .= $this->getUploadError();

            if ($errors) {
                //print $errors;
                $params['success_msg'] = '';
                $params['error_msg'] = $errors;
                $params['error_code'] = '';

                return new ViewModel($params);
            }


            $xmlFields = $this->getXmlFields();

            $arr_csv_header_fields = $this->get_csv_header_fields();
            $arr_csv_data = $this->get_csv_data();
            $totalRows = $this->get_total_rows();
            if ($endRow && $endRow <= $totalRows) {
                $totalRows = $endRow;
            }
            //echo '<pre>';
            //print_r($arr_csv_header_fields);
            //print_r(array_unique($arr_csv_data['main_category']));
            //print_r(array_unique($arr_csv_data['sub_category']));
            /*$a = array();
            foreach($arr_csv_data['main_category'] as $k=>$cat) {
                $a[$cat][] = $arr_csv_data['sub_category'][$k];
            }
            print_r($a);*/
            //echo '</pre>';
            //die();


        
        
            $mappingArr = array('customer_name' => (string) $xmlFields->customer_name,
                                'supplier_name' => (string) $xmlFields->supplier_name,
                                //'amount' => (string) $xmlFields->amount, // for Sage
                                //'transaction_description' => (string) $xmlFields->transaction_description, // for Sage
                                'currency' => (string) $xmlFields->currency, 
                                'account_name_financial' => (string) $xmlFields->account_name_financial, // for Qb
                                'bank_account_id' => (string) $xmlFields->bank_account_id, // for Sage
                                'payment_made' => (string) $xmlFields->payment_made, 
                                'payment_type' => (string) $xmlFields->payment_type,
                                'account_name' => (string) $xmlFields->account_name,
                                'account_category' => (string) $xmlFields->account_category,
                                'account_subcategory' => (string) $xmlFields->account_subcategory,
                                'item_name' => (string) $xmlFields->item_name,
                                'description' => (string) $xmlFields->description,
                                'quantity' => (string) $xmlFields->quantity,
                                'unit_price' => (string) $xmlFields->unit_price,
                                //'tax_percentage' => (string) $xmlFields->tax_percentage, // for Sage 
                                //'discount_percentage' => (string) $xmlFields->discount_percentage, // for Sage 
                                //'tax' => (string) $xmlFields->tax,  // for Sage
                                //'discount' => (string)$xmlFields->discount,  // for Sage
                                //'tax_amount_total' => (string) $xmlFields->tax_amount_total,  // for Sage
                                //'discount_percent_total' => (string) $xmlFields->discount_percent_total,
                                'due_date' => (string) $xmlFields->due_date,
                                'txn_date' => (string) $xmlFields->txn_date,
                );

//            echo '<pre>';
//            print_r($mappingArr);
//            echo '</pre>';
//            die();
            
            $customersExisting = $this->getObjectManager()->getRepository('\Application\Entity\Customer')->findBy(array('user_id' => $this->getUserId()));
            $customerNamesExisting = array();
            foreach($customersExisting as $obj) {
                $customerNamesExisting[$obj->getId()] = $obj->getName();
            }
            
            $suppliersExisting = $this->getObjectManager()->getRepository('\Application\Entity\Supplier')->findBy(array('user_id' => $this->getUserId()));
            $supplierNamesExisting = array();
            foreach($suppliersExisting as $obj) {
                $supplierNamesExisting[$obj->getId()] = $obj->getName();
            }
            
            $accountsFinancialExisting = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId(), 'account_type' => 'Bank'));
            $accountNamesFinancialExisting = array();
            foreach($accountsFinancialExisting as $obj) {
                $accountNamesFinancialExisting[$obj->getId()] = $obj->getName();
            }
            
            $bankAccountExisting = $this->getObjectManager()->getRepository('\Application\Entity\BankAccount')->findBy(array('user_id' => $this->getUserId()));
            $bankAccountNamesExisting = array();
            foreach($bankAccountExisting as $obj) {
                $bankAccountNamesExisting[$obj->getId()] = $obj->getName();
            }
            
            $accountsExisting = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
            $accountNamesExisting = array();
            foreach($accountsExisting as $obj) {
                $accountNamesExisting[$obj->getId()] = $obj->getName();
            }
            
            $itemsExisting = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findBy(array('user_id' => $this->getUserId()));
            $itemNamesExisting = array();
            foreach($itemsExisting as $obj) {
                $itemNamesExisting[$obj->getId()] = $obj->getItemName();
            }
            
            // Loop for all data rows
            for($row = $startRow; $row <= $totalRows; $row++) {
                
                $transactionData = array();
                
                //$arr_csv_data[$mappingArr['unit_price']][$row] = abs($arr_csv_data[$mappingArr['unit_price']][$row]); // remove
                
                if ($arr_csv_data[$mappingArr['unit_price']][$row] > 0) {
                    $transactionData['customer_id'] = $this->getCustomerId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $customerNamesExisting);
                    $transactionData['item_id'][1] = $this->getItemId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $itemNamesExisting);
                } else {
                    $transactionData['supplier_id'] = $this->getSupplierId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $supplierNamesExisting);
                    $transactionData['account_id_financial'] = $this->getAccountIdFinancial($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $accountNamesFinancialExisting);
                    $transactionData['account_id'][1] = $this->getAccountId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $accountNamesExisting);
                }
                
                //$transactionData['bank_account_id'] = $this->getBankAccountId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $bankAccountNameExisting);
                
                $transactionData['currency'] = isset($arr_csv_data[$mappingArr['currency']][$row]) ? $arr_csv_data[$mappingArr['currency']][$row] : '';

                $transactionData['payment_made'] = 1;
                if (isset($arr_csv_data[$mappingArr['payment_made']][$row])) {
                    $transactionData['payment_made'] = $arr_csv_data[$mappingArr['payment_made']][$row];
                } else if (!empty($arr_csv_data[$mappingArr['due_date']][$row]) 
                           && strtotime($arr_csv_data[$mappingArr['due_date']][$row]) >= mktime(0, 0, 0, date('m'), date('d'), date('y'))) {
                    $transactionData['payment_made'] = 0;
                }
                
                if (!empty($arr_csv_data[$mappingArr['payment_type']][$row])) {
                    $transactionData['payment_type'] = $arr_csv_data[$mappingArr['payment_type']][$row];
                } else {
                    $transactionData['payment_type'] = 'Cash';
                }
                
                if (!empty($arr_csv_data[$mappingArr['due_date']][$row])) {
                    $transactionData['due_date'] = $arr_csv_data[$mappingArr['due_date']][$row];
                } else {
                    $transactionData['due_date'] = date("Y-m-d");
                }
                
                if (!empty($arr_csv_data[$mappingArr['txn_date']][$row]) 
                    && strtotime($arr_csv_data[$mappingArr['txn_date']][$row]) >= mktime(0, 0, 0, date('m'), date('d'), date('y'))) {
                    $transactionData['txn_date'] = $arr_csv_data[$mappingArr['txn_date']][$row];
                } else {
                    $transactionData['txn_date'] = date("Y-m-d");
                }
                
                $transactionData['unit_price'][1] = isset($arr_csv_data[$mappingArr['unit_price']][$row]) ? $arr_csv_data[$mappingArr['unit_price']][$row] : 0;
                
                $transactionData['quantity'][1] = isset($arr_csv_data[$mappingArr['quantity']][$row]) ? $arr_csv_data[$mappingArr['quantity']][$row] : 1;
                
                $transactionData['description'][1] = isset($arr_csv_data[$mappingArr['description']][$row]) ? $arr_csv_data[$mappingArr['description']][$row] : '';
                
                

                /*echo '<pre>';
                print_r($transactionData);
                echo '</pre>';*/
                
                $data = $this->processTransaction($transactionData);
                //die('testing');
                /*echo '<pre>';
                print_r($data);
                echo '</pre>';*/
                
                if (empty($data)) break;
                
                echo 'Transaction created for row #' . $row 
                        . ': SerId-' . $data['vendor_service_id'] 
                        . ', TxnId-' . $data['transaction_id']
                        . ', TotalAmt-' . $data['total_amt'] 
                        . ', Type-' . $data['service_type'] .  '<br /><br />';
            }
        }

        $params['success_msg'] = $this->_success_msg;
        $params['error_msg'] = $this->_error_msg;
        $params['error_code'] = $this->_error_code;
        $params['access_token'] = $this->getAccessToken();
                
        return new ViewModel($params);
    }
    
    private function processTransaction($createData)
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_POST)
                ->setContent(json_encode($createData));
        $request->getHeaders()->addHeaders(array(
            'Content-type' => 'application/json',
            'Accept' => 'application/json'
        ));

        $apiController = $this->prepareTransactionApi($request);
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();
        
        if ($response->getStatusCode() == 200 && $result->data) {
            return $result->data;
        } else {
            $this->_error_code = $result->error_code;
            $this->_error_msg = $result->error_msg;
            return false;
        }
    }
    
    private function getCustomerId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $customerNamesExisting)
    {
        if (in_array($mappingArr['customer_name'], $arr_csv_header_fields)) {
            $customerName = $arr_csv_data[$mappingArr['customer_name']][$row];
            // This Customer was not created previously
            if (!in_array($customerName, $customerNamesExisting)) {
                echo 'Customer: ' . $customerName . ' being created...<br />';
                // Create the Customer
                $createData = array('customer_name' => $customerName,
                                    'display_name' => $customerName,
                                    'name' => $customerName,
                                    'surname' => '',
                                    'address1' => '',
                                    'address2' => '',
                                    'city' => '',
                                    'postcode' => '',
                                    'country' => '');
                $customerCreated = $this->addAnyEntity($createData, 'customer');
                $customerId = $customerCreated['id'];
            } else {
                $customerId = array_search($customerName, $customerNamesExisting);
            }
        } else {
            //echo 'Use default customer';
            $customerId = array_search($this->_default_customer_name, $customerNamesExisting);
        }
        
        return $customerId;
    }
    
    private function getSupplierId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $supplierNamesExisting)
    {
        if (in_array($mappingArr['supplier_name'], $arr_csv_header_fields)) {
            $supplierName = $arr_csv_data[$mappingArr['supplier_name']][$row];
            // This Supplier was not created previously
            if (!in_array($supplierName, $supplierNamesExisting)) {
                echo 'Supplier: ' . $supplierName . ' being created...<br />';
                // Create the Supplier
                $createData = array('supplier_name' => $supplierName,
                                    'display_name' => $supplierName,
                                    'name' => $supplierName,
                                    'surname' => '',
                                    'address1' => '',
                                    'address2' => '',
                                    'city' => '',
                                    'postcode' => '',
                                    'country' => '');
                $supplierCreated = $this->addAnyEntity($createData, 'supplier');
                $supplierId = $supplierCreated['id'];
            } else {
                $supplierId = array_search($supplierName, $supplierNamesExisting);
            }
        } else {
            //echo 'Use default supplier';
            $supplierId = array_search($this->_default_supplier_name, $supplierNamesExisting);
        }
        
        return $supplierId;
    }
    
    private function getAccountIdFinancial($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $accountNamesFinancialExisting)
    {
        if (in_array($mappingArr['account_name_financial'], $arr_csv_header_fields)) {
            $accountNameFinancial = $arr_csv_data[$mappingArr['account_name_financial']][$row];
            // This Account was not created previously
            if (!in_array($accountNameFinancial, $accountNamesFinancialExisting)) {
                echo 'Account financial: ' . $accountNameFinancial . ' being created...<br />';
                $accountCategory = 'Bank';
                $accountSubcategory = 'Cash on hand';

                // Create the Account
                $createData = array('name' => $accountNameFinancial,
                                    'account_type' => $accountCategory,
                                    'account_sub_type' => $accountSubcategory,
                                    'category_id' => '2',
                                    'category_description' => 'Cost of Sales');
                $accountCreated = $this->addAnyEntity($createData, 'account');
                $accountId = $accountCreated['id'];
            } else {
                $accountId = array_search($accountNameFinancial, $accountNamesFinancialExisting);
            }
        } else {
            // Use default account
            $accountId = array_search($this->_default_account_financial_name, $accountNamesFinancialExisting);
        }
        
        return $accountId;
    }
    
    private function getBankAccountId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $bankAccountNamesExisting)
    {
        if (in_array($mappingArr['bank_account_name'], $arr_csv_header_fields)) {
            $bankAccountName = $arr_csv_data[$mappingArr['bank_account_name']][$row];
            // This BankAccount was not created previously
            if (!in_array($bankAccountName, $bankAccountNamesExisting)) {
                echo 'Bank account: ' . $bankAccountName . ' being created...<br />';
                // Create the BankAccount
                $createData = array('name' => $bankAccountName,
                                    'bank_account_type' => $bankAccountCategory,
                                    'bank_account_sub_type' => $bankAccountSubcategory,
                                    'category_id' => '2',
                                    'category_description' => 'Cost of Sales');
                $bankAccountCreated = $this->addAnyEntity($createData, 'account');
                $bankAccountId = $bankAccountCreated['id'];
            } else {
                $bankAccountId = array_search($bankAccountName, $bankAccountNamesExisting);
            }
        } else {
            // Use default BankAccount
            $bankAccountId = array_search($this->_default_bank_account_name, $bankAccountNamesExisting);
        }
        
        return $bankAccountId;
    }
    
    private function getAccountId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $accountNamesExisting)
    {
        if (in_array($mappingArr['account_name'], $arr_csv_header_fields)) {
            $accountName = $arr_csv_data[$mappingArr['account_name']][$row];
            // This Account was not created previously
            if (!in_array($accountName, $accountNamesExisting) && !in_array($accountName, $this->alreadyCreadedArr['account'])) {
                echo 'Account: ' . $accountName . ' being created...<br />';
                if (in_array($mappingArr['account_category'], $arr_csv_header_fields)) {
                    $accountCategory = $this->getXmlAccountMappedCategoryName($arr_csv_data[$mappingArr['account_category']][$row], 'Qb');
                    $accountSubcategory = $this->getXmlAccountMappedSubCategoryName($arr_csv_data[$mappingArr['account_category']][$row], $arr_csv_data[$mappingArr['account_subcategory']][$row], 'Qb');
                } else {
                    $accountCategory = $arr_csv_data[$mappingArr['unit_price']][$row] > 0 ? 'Income'  : 'Expense';
                }

                $accountSubcategory = str_replace(array('&amp;', '&', '-', '/'), ' ', $accountSubcategory);
                $accountSubcategory = str_replace(' ', '', ucwords($accountSubcategory));
                // Create the Account
                $createData = array('name' => $accountName,
                                    'account_type' => $accountCategory,
                                    'account_sub_type' => $accountSubcategory,
                                    'category_id' => '2',
                                    'category_description' => 'Cost of Sales');
                echo '<pre>';
                print_r($createData);
                echo '</pre>';
                //die();
                $accountCreated = $this->addAnyEntity($createData, 'account');
                $accountId = $accountCreated['id'];
                $this->alreadyCreadedArr['account'][$accountId] = $accountName;
            } else if (in_array($accountName, $this->alreadyCreadedArr['account'])) {
                $accountId = array_search($accountName, $accountNamesExisting);
            } else {
                $accountId = array_search($accountName, $accountNamesExisting);
            }
        } else {
            // Use default account
            $accountId = array_search($this->_default_account_name, $accountNamesExisting);
        }
        
        return $accountId;
    }
    
    private function getItemId($mappingArr, $arr_csv_header_fields, $arr_csv_data, $row, $itemNamesExisting)
    {
        if (in_array($mappingArr['item_name'], $arr_csv_header_fields)) {
            $itemName = $arr_csv_data[$mappingArr['item_name']][$row];
            // This Item was not created previously
            if (!in_array($itemName, $itemNamesExisting)) {
                echo 'Item: ' . $itemName . ' being created...<br />';
                // Create the Item
                $createData = array('item_name' => $itemName,
                                    'item_type' => '',
                                    'income_account_id' => '1',
                                    'expense_account_id' => '',
                                    'description' => '',
                                    'price' => 0);
                $itemId = $itemCreated['id'];
            } else {
                $itemId = array_search($itemName, $itemNamesExisting);
            }
        } else {
            // Use default item
            $itemId = array_search($this->_default_item_name, $itemNamesExisting);
        }
        
        return $itemId;
    }

    private function addAnyEntity($createData, $entityName = 'account')
    {
        $request = $this->preparePostRequest($createData);
        $apiController = $this->prepareAnyApi($request, $entityName);
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            $this->_success_msg .= "Default {$entityName} created successfully<br />";
            return $result->data;
        } else {
            $this->_error_msg .= $result->error_msg . '<br />';
            return false;
        }
    }
    
    private function get_csv_header_fields()
    {
        if (empty($this->_arr_csv_header_fields) || empty($this->_arr_csv_data)) {
            $this->processCsv();
        }
        
        return $this->_arr_csv_header_fields;
    }
    
    private function get_csv_data()
    {
        if (empty($this->_arr_csv_header_fields) || empty($this->_arr_csv_data)) {
            $this->processCsv();
        }
        
        return $this->_arr_csv_data;
    }
    
    private function get_total_rows()
    {
        if (empty($this->_arr_csv_header_fields) || empty($this->_arr_csv_data)) {
            $this->processCsv();
        }
        
        return $this->_total_rows;
    }
    
    private function processCsv()
    {
        $this->_arr_csv_header_fields = array();
        $this->_arr_csv_data = array();
        
        $uploadedCsvFile = $this->getDestination() . $this->getCsvFileName();
        $fpointer = fopen($uploadedCsvFile, "r");
        if ($fpointer) {
            $arr = fgetcsv($fpointer, 10*1024, $this->_field_separate_char);
            if(is_array($arr) && !empty($arr)) {
                    foreach($arr as $val) {
                        if(trim($val) != "") {
                            $this->_arr_csv_header_fields[] = trim($val);
                        }
                    }
            }
            $columnNum = count($this->_arr_csv_header_fields);
            $row = 1;
            while($arr = fgetcsv($fpointer, 10*1024, $this->_field_separate_char)) {
                if(count($arr) == $columnNum) {
                    foreach($arr as $k => $val) {
                        $this->_arr_csv_data[$this->_arr_csv_header_fields[$k]][$row] = $val;
                    }
                }
                $row++;
                //if ($row > 20) break; // remove
            }
            $this->_total_rows = $row - 1;
            unset($arr);
            fclose($fpointer);
        } else {
            $this->_error_msg .= "file cannot be opened: ".(""==$this->_csv_file_path ? "[empty]" : @mysql_escape_string($this->_csv_file_path)) . '<br />';
        }
    }
    
    private function processXml()
    {
        $uploadedXmlFile = $this->getDestination() . $this->getXmlFileName();
        $xml = simplexml_load_file($uploadedXmlFile) or die("Error: Cannot create object");

        $this->_xml_fields = $xml->fields[0];
 
        $accounts = array();
        foreach($xml->accounts[0] as $ob) {
            //$accounts["{$ob->category->pms}"]['qb'] = "{$ob->category->qb}";
            $accounts["{$ob->category->pms}"]['category']['qb'] =  "{$ob->category->qb}";
            foreach($ob->subcategory as $subOb) {
                    $accounts["{$ob->category->pms}"]['subcategory']["{$subOb->pms}"]['qb'] =  "{$subOb->qb}";
            }
        }
        $this->_xml_accounts = $accounts;
        
//        echo '<pre>';
//        echo $xml->fields[0]->account_category->attributes()->name;
//        echo '</pre>';
    }
    
    public function getXmlFields()
    {
        if (empty($this->_xml_fields) || empty($this->_xml_accounts)) {
            $this->processXml();
        }
        
        return $this->_xml_fields;
    }
    
    public function getXmlAccounts()
    {
        if (empty($this->_xml_fields) || empty($this->_xml_accounts)) {
            $this->processXml();
        }
        
        return $this->_xml_accounts;
    }
    
    public function getXmlAccountMappedCategoryName($categoryName, $accountingName)
    {
        $accountingName = strtolower($accountingName);
        $accounts = $this->getXmlAccounts();
        
        return $accounts["{$categoryName}"]['category']["{$accountingName}"];
    }
    
    public function getXmlAccountMappedSubCategoryName($categoryName, $subCategoryName, $accountingName)
    {
        $accountingName = strtolower($accountingName);
        $accounts = $this->getXmlAccounts();
        
        return $accounts["{$categoryName}"]['subcategory']["{$subCategoryName}"]["{$accountingName}"];
    }

    public function editAction()
    {
        $params = array();

        if ($this->request->isPost()) {
            $updatedData = array('customer_id' => $this->getRequest()->getPost('customer_id'),
                                 'description' => $this->getRequest()->getPost('description'),
                                 'account_id' => $this->getRequest()->getPost('account_id'),
                                 'unit_price' => $this->getRequest()->getPost('unit_price'),
                                 'currency' => $this->getRequest()->getPost('currency'),
                                 'tax_id' => $this->getRequest()->getPost('tax_id'));
            
            $request    = new Request();
            $request->setMethod(Request::METHOD_PUT)
                    ->setContent(\Zend\Json\Json::encode($updatedData));
            $request->getHeaders()->addHeaders(array(
                'Content-type' => 'application/json',
                'Accept' => 'application/json'
            ));
            
            $apiController = $this->prepareApi($request);
            $result = $apiController->dispatch($request);
            $response = $apiController->getResponse();
            
            if ($response->getStatusCode() == 200 && $result->data) {
                return $this->redirect()->toRoute(null, array('controller' => 'transaction', 'action'=>'index'));
            } else {
                $params['error_code'] = $result->error_code;
                $params['error_msg'] = $result->error_msg;
            }
        }

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $params['transaction'] = $apiController->dispatch($request)->data;
        
        try {
            $customers = $this->getObjectManager()->getRepository('\Application\Entity\Customer')->findAll();
            $params['customers'] = $customers;

            $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findBy(array('user_id' => $this->getUserId()));
            $params['accounts'] = $accounts;
            
            $items = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findBy(array('user_id' => $this->getUserId()));
            $params['items'] = $items;

            $taxes = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findBy(array('user_id' => $this->getUserId()));
            $params['taxes'] = $taxes;
        } catch(\Exception $e) {
            $params['error_code'] = $e->getCode();
            $params['error_msg'] = $e->getMessage();
        }
        
        return new ViewModel($params);
    }

    public function viewAction()
    {
        $params = array();

        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        $apiController = $this->prepareApi($request);
        $params['transaction'] = $apiController->dispatch($request)->data;
        
        return new ViewModel($params);
    }
    
    public function deleteAction()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_DELETE);
        $request->getHeaders()->addHeaders(array(
                'Content-type' => 'application/json',
                'Accept' => 'application/json'
            ));
        
        $apiController = $this->prepareApi($request);
        $result = $apiController->dispatch($request);
        $response = $apiController->getResponse();

        if ($response->getStatusCode() == 200 && $result->data) {
            return $this->redirect()->toRoute(null, array('controller' => 'transaction', 'action'=>'index'));
        } else {
            $params['error_code'] = $result->error_code;
            $params['error_msg'] = $result->error_msg;
            echo '<pre>';
            print_r($params);
            echo '</pre>';
            die();
        }
    }

    private function prepareGetRequest()
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_GET);
        
        return $request;
    }

    private function preparePostRequest($createData)
    {
        $request    = new Request();
        $request->setMethod(Request::METHOD_POST)
                ->setContent(json_encode($createData));
        $request->getHeaders()->addHeaders(array(
            'Content-type' => 'application/json',
            'Accept' => 'application/json'
        ));

        return $request;
    }
    
    private function prepareAnyApi(Request $request, $inputType, $id = 0)
    {
        $accessToken = $this->getAccessToken();
        //$id = (int) $this->params()->fromRoute('id', 0);
        if ($id) {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken, 'id' => $id)));
        } else {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken)));
        }
        
        $apiController = new \Input\Controller\InputController();
        $apiController->setServiceLocator($this->getServiceLocator());

        // Need to send user_id or token
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => $inputType));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }
    
    private function prepareTransactionApi(Request $request)
    {
        $accessToken = $this->getAccessToken();
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id) {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken, 'id' => $id)));
        } else {
            $request->setQuery(new \Zend\Stdlib\Parameters(array('access_token' => $accessToken)));
        }
        
        
        $apiController = new \Input\Controller\InputController();
        $apiController->setServiceLocator($this->getServiceLocator());
        
        // Need to send user_id or token
        $routeMatch       = new \Zend\Mvc\Router\RouteMatch(array('controller' => 'input', 'input-type' => 'transaction'));

        $event      = new \Zend\Mvc\MvcEvent();
        $event->setRouteMatch($routeMatch);
        $event->setRequest($request);

        $apiController->setEvent($event);

        return $apiController;
    }
    
    
    //START: Functions to Change Default Settings
    private function setDestination($newDestination) {
        $this->destination = $newDestination;
    }
    
    private function getDestination() {
        return $this->destination;
    }
    
    private function setCsvFileName($newFileName) {
        $this->csvFileName = $newFileName;
    }
    
    private function getCsvFileName() {
        return $this->csvFileName;
    }
    
    private function setXmlFileName($newFileName) {
        $this->xmlFileName = $newFileName;
    }
    
    private function getXmlFileName() {
        return $this->xmlFileName;
    }
    
    private function setMaxSize($newSize) {
        $this->maxSize = $newSize;
    }
    
    private function setAllowedExtensions($newExtensions) {
        if (is_array($newExtensions)) {
            $this->allowedExtensions = $newExtensions;
        } else {
            $this->allowedExtensions = array($newExtensions);
        }
    }
    //END: Functions to Change Default Settings

    //START: Process File Functions
    private function upload($file, $fileType = 'csv') {

        $this->validate($file);
      
        if ($fileType == 'csv') {
            $fileName = $this->getCsvFileName();
        } else {
            $fileName = $this->getXmlFileName();
        } 

        if ($this->_upload_error) {
            return false;
        } else {
            move_uploaded_file($file['tmp_name'], $this->destination.$fileName) or $this->_upload_error .= 'Destination Directory Permission Problem.<br />';
            if ($this->_upload_error) {
                return false;
            }
            
            return true;
        }
    }
    
    private function delete($file) {
        $error = '';
        if (file_exists($file)) {
            unlink($file) or $error .= 'Destination Directory Permission Problem.<br />';
        } else {
            $error .= 'File not found! Could not delete: '.$file.'<br />';
        }

        if ($error) {
            $this->_upload_error = $error;
            return false;
        }
        
        return true;
    }
    //END: Process File Functions

    //START: Helper Functions
    private function validate($file) {

        $error = '';

        //check file exist
        if (empty($file['name'][0])) {
            $error .= 'No file found.<br />';
        }
        //check allowed extensions
        if (!in_array($this->getExtension($file),$this->allowedExtensions)) {
            $error .= 'Extension is not allowed.<br />';
        }
        //check file size
        if ($file['size'][0] > $this->maxSize) {
            $error .= 'Max File Size Exceeded. Limit: '.$this->maxSize.' bytes.<br />';
        }

        if ($error) {
            $this->_upload_error = $error;
            return false;
        }
        
        return true;
    }
    
    private function getExtension($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $ext = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        return $ext;
    }
    
    private function getUploadError()
    {
        return $this->_upload_error;
    }

}
