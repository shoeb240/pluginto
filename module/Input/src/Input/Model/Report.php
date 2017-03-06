<?php
namespace Input\Model;

use Application\Model\BaseModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class Report extends BaseModel
{
    /* TODO: use PHPDoc's DocBlock */

    public function getHydrator($entity)
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, "Application\Entity\\" . $entity, false);
        }

        return $this->_hydrator;
    }
    
    public function fetchBalanceSheetReport()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $reportAccountingModel = $this->getServiceLocator()->get('ReportModelWrapper')->get($this->getUserAccountingVendor()); //Qb
        
        $vendorAccountArr = $reportAccountingModel->getReport($this->getUserId(), 'BalanceSheet');
        
        return $vendorAccountArr;
    }
    
    public function fetchProfitAndLossReport()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $reportAccountingModel = $this->getServiceLocator()->get('ReportModelWrapper')->get($this->getUserAccountingVendor()); //Qb
        $vendorAccountArr = $reportAccountingModel->getReport($this->getUserId(), 'ProfitAndLoss');

        return $vendorAccountArr;
    }
    
    public function fetchSalesReport($serviceType = null, $customerId = null, $supplierId = null, $itemId = null, $accountId = null)
    {
//        $serviceType = null; // 'Invoice';
//        $customerId = null;
//        $supplierId = null;
//        $itemId = null;
//        $accountId = 42;
//        echo $serviceType . '==' . $customerId . '==' . $supplierId . '==' . $itemId . '==' . $accountId;
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $sql = '';
        $params = array();
        //$sql .= 's.user_id = :user_id';
        // $params['user_id'] = $this->getUserId();
        if (!empty($serviceType)) {
            if ($sql != '') { $sql .= ' AND '; }
            $sql .= 's.service_type = :service_type';
            $params['service_type'] = $serviceType;
        }
        if (!empty($customerId)) {
            if ($sql != '') { $sql .= ' AND '; }
            $sql .= 's.customer_id = :customer_id';
            $params['customer_id'] = $customerId;
        } else if (!empty($supplierId)) {
            if ($sql != '') { $sql .= ' AND '; }
            $sql .= 's.supplier_id = :supplier_id';
            $params['supplier_id'] = $supplierId;
        }
        if (!empty($itemId)) {
            if ($sql != '') { $sql .= ' AND '; }
            $sql .= 'sl.item_id = :item_id';
            $params['item_id'] = $itemId;
        } else if (!empty($accountId)) {
            if ($sql != '') { $sql .= ' AND '; }
            $sql .= 'sl.account_id = :account_id';
            $params['account_id'] = $accountId;
        }

        if ($sql == '') { $sql .= ' 1 '; }
        
        try {
            $adapter = $this->getServiceLocator()->get('\Zend\Db\Adapter\Adapter');
        
            $sql_a = "SELECT s.* FROM Service s "
                    . " Left Join ServiceLine sl ON s.id = sl.service_id "
                    . " WHERE  " . $sql 
                    . " GROUP BY s.id";
            //echo $sql_a . '<br />';
            $statement = $adapter->query($sql_a);
            $results = $statement->execute($params);
            foreach ($results as $row) {
                $dataArray['table_data'][] = $row;
            }
            
            
            $sql_b = "SELECT sum(s.total_amt) as total, Month(txn_date) as month FROM Service s"
                    . " WHERE s.service_type = 'SalesReceipt' OR s.service_type = 'CustomerReceipt'"
                    . " GROUP BY Month(txn_date)";
            $statement = $adapter->query($sql_b);
            $results = $statement->execute($params);
            foreach ($results as $row) {
                $dataArray['grp_data_sales'][] = $row;
            }
            
            
            $sql_c = "SELECT sum(s.total_amt) as total, Month(txn_date) as month FROM Service s"
                    . " WHERE s.service_type = 'Purchase' OR s.service_type = 'SupplierPayment'"
                    . " GROUP BY Month(txn_date)";
            $statement = $adapter->query($sql_c);
            $results = $statement->execute($params);
            foreach ($results as $row) {
                $dataArray['grp_data_expenses'][] = $row;
            }
            
            
            $sql_c = "SELECT sum(s.total_amt) as total, Month(txn_date) as month FROM Service s"
                    . " WHERE s.service_type = 'Purchase' OR s.service_type = 'SupplierPayment'"
                    . " GROUP BY Month(txn_date)";
            $statement = $adapter->query($sql_c);
            $results = $statement->execute($params);
            foreach ($results as $row) {
                $dataArray['grp_data_expenses'][] = $row;
            }
            
            
            $sql_d = "SELECT sp.supplier_name, sum(s.total_amt) as total FROM Service s "
                    . " INNER JOIN Supplier sp ON s.supplier_id = sp.vendor_supplier_id"
                    . " WHERE s.service_type = 'Purchase' OR s.service_type = 'SupplierPayment'"
                    . " GROUP BY s.supplier_id";
            $statement = $adapter->query($sql_d);
            $results = $statement->execute($params);
            foreach ($results as $row) {
                $dataArray['grp_data_supplier'][] = $row;
            }
            
            
            $sql_e = "SELECT cs.customer_name, sum(s.total_amt) as total FROM Service s "
                    . " INNER JOIN Customer cs ON s.customer_id = cs.vendor_customer_id"
                    . " WHERE s.service_type = 'SalesReceipt' OR s.service_type = 'CustomerReceipt'"
                    . " GROUP BY s.customer_id";
            $statement = $adapter->query($sql_e);
            $results = $statement->execute($params);
            foreach ($results as $row) {
                $dataArray['grp_data_customer'][] = $row;
            }
            
            
            $sql_f = "SELECT s.service_type, ac.name, sum(sl.amount) as total FROM Service s "
                    . " INNER JOIN ServiceLine sl ON s.id = sl.service_id "
                    . " INNER JOIN Account ac ON sl.account_id = ac.vendor_account_id"
                    . " WHERE s.service_type = 'SalesReceipt' OR s.service_type = 'CustomerReceipt' OR s.service_type = 'Purchase' OR s.service_type = 'SupplierPayment'"
                    . " GROUP BY sl.account_id, s.service_type";
            $statement = $adapter->query($sql_f);
            $results = $statement->execute($params);
            foreach ($results as $row) {
                $dataArray['grp_data_account'][] = $row;
            }
            
            
            $sql_g = "SELECT s.service_type, it.item_name, sum(sl.amount) as total FROM Service s "
                    . " INNER JOIN ServiceLine sl ON s.id = sl.service_id "
                    . " INNER JOIN Item it ON sl.item_id = it.vendor_item_id"
                    . " WHERE s.service_type = 'SalesReceipt' OR s.service_type = 'CustomerReceipt' OR s.service_type = 'Purchase' OR s.service_type = 'SupplierPayment'"
                    . " GROUP BY sl.item_id, s.service_type";
            //echo $sql_g;
            $statement = $adapter->query($sql_g);
            $results = $statement->execute($params);
            foreach ($results as $row) {
                $dataArray['grp_data_item'][] = $row;
            }
            
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    
        
//        echo '<pre>';
//        print_r($dataArray['grp_data_item']);
//        echo '</pre>';
//        echo $sql;

                
        return $dataArray;

    }
    
    
}