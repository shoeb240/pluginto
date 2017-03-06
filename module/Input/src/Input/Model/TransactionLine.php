<?php
namespace Input\Model;

use Application\Model\BaseModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;
use Doctrine\ORM\Query\ResultSetMapping;

class TransactionLine extends BaseModel
{
    /* TODO: use PHPDoc's DocBlock */
    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\TransactionLine', false);
        }

        return $this->_hydrator;
    }
    
    public function fetchAll()
    {
        try {
            $invoiceLines = $this->getObjectManager()->getRepository('\Application\Entity\TransactionLine')->findAll();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($invoiceLines) {
            foreach ($invoiceLines as $invoiceLine) {
                $dataArray[] = $hydrator->extract($invoiceLine);
            }
        }
        
        return $dataArray;
    }

    public function getEntityById($id)
    {
        try {
            $invoiceLine = $this->getObjectManager()->find('\Application\Entity\TransactionLine', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    
        
        if (!$invoiceLine) {
            throw new PlugintoException(PlugintoException::INVALID_INVOICE_LINE_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_INVOICE_LINE_ID_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($invoiceLine);

        return $dataArray;
    }

    public function createEntity($data)
    {
        $invoiceLine = new \Application\Entity\TransactionLine();
        
        if (isset($data['transaction_id'])) {
            $invoiceLine->setTransactionId($data['transaction_id']);
        }
        if (isset($data['line_num'])) {
            $invoiceLine->setLineNum($data['line_num']);
        }
        if (isset($data['item_id'])) {
            $invoiceLine->setItemId($data['item_id']);
        }
        if (isset($data['account_id'])) {
            $invoiceLine->setAccountId($data['account_id']);
        }
        if (isset($data['description'])) {
            $invoiceLine->setDescription($data['description']);
        }
        if (isset($data['quantity'])) {
            $invoiceLine->setQuantity($data['quantity']);
        }
        if (isset($data['unit_price'])) {
            $invoiceLine->setUnitPrice($data['unit_price']);
        }
        if (isset($data['tax_percentage'])) {
            $invoiceLine->setTaxPercentage($data['tax_percentage']);
        }
        if (isset($data['tax'])) {
            $invoiceLine->setTax($data['tax']);
        }
        if (isset($data['discount_percentage'])) {
            $invoiceLine->setDiscountPercentage($data['discount_percentage']);
        }
        if (isset($data['discount'])) {
            $invoiceLine->setDiscount($data['discount']);
        }
        if (isset($data['amount'])) {
            $invoiceLine->setAmount($data['amount']);
        }
        if (isset($data['detail_type'])) {
            $invoiceLine->setDetailType($data['detail_type']);
        }

        try {
            $this->getObjectManager()->persist($invoiceLine);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();

        $dataArray = $hydrator->extract($invoiceLine);

        return $dataArray;
    }

    public function deleteEntityByTransactionId($transactionId)
    {
        try {
            $numDeleted = $this->getObjectManager()->getConnection()->executeUpdate('DELETE FROM TransactionLine WHERE transaction_id = ?', array($transactionId));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        if ($numDeleted > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    public function createAll($lineObjectArr)
    {
        foreach($lineObjectArr as $transactionId => $objectArr) {
            foreach ($objectArr as $line) {
                $invoiceLine = new \Application\Entity\TransactionLine();

                $itemRef = isset($line->SalesItemLineDetail) ? $line->SalesItemLineDetail->ItemRef : '';

                $invoiceLine->setTransactionId($transactionId);
                $invoiceLine->setLineNum($line->LineNum);
                $invoiceLine->setAccountId($itemRef);
                $invoiceLine->setDescription($line->Description);
                $invoiceLine->setAmount($line->Amount);
                $invoiceLine->setDetailType($line->DetailType);

                $this->getObjectManager()->persist($invoiceLine);
            }
        }
        
        try {
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        return true;
    }
    
    public function getEntityByTransactionId($transactionId)
    {
        try {
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('\Application\Entity\TransactionLine', 's');
            $rsm->addFieldResult('s', 'id', 'id');
            $rsm->addFieldResult('s', 'transaction_id', 'transaction_id');
            $rsm->addFieldResult('s', 'line_num', 'line_num');
            $rsm->addFieldResult('s', 'item_id', 'item_id');
            $rsm->addFieldResult('s', 'account_id', 'account_id');
            $rsm->addFieldResult('s', 'description', 'description');
            $rsm->addFieldResult('s', 'quantity', 'quantity');
            $rsm->addFieldResult('s', 'unit_price', 'unit_price');
            $rsm->addFieldResult('s', 'amount', 'amount');
            $rsm->addFieldResult('s', 'tax_percentage', 'tax_percentage');
            $rsm->addFieldResult('s', 'tax', 'tax');
            $rsm->addFieldResult('s', 'discount_percentage', 'discount_percentage');
            $rsm->addFieldResult('s', 'discount', 'discount');
            $rsm->addFieldResult('s', 'detail_type', 'detail_type');

            $query = $this->getObjectManager()->createNativeQuery('SELECT * FROM TransactionLine WHERE transaction_id = ?', $rsm);
            $query->setParameter(1, $transactionId);

            $invoiceLines = $query->getResult();

        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();
        $dataArray = array();
        
        if (count($invoiceLines) > 0) {
            foreach ($invoiceLines as $invoiceLine) {
                $dataArray[] = $hydrator->extract($invoiceLine);
            }
        }
        
        return $dataArray;
    }
}