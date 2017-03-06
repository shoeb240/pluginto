<?php
namespace Application\Model;

use Application\Model\BaseModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;
use Doctrine\ORM\Query\ResultSetMapping;

class ServiceLine extends BaseModel
{
    /* TODO: use PHPDoc's DocBlock */
    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\ServiceLine', false);
        }

        return $this->_hydrator;
    }
    
    public function fetchAll()
    {
        try {
            $invoiceLines = $this->getObjectManager()->getRepository('\Application\Entity\ServiceLine')->findAll();
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
            $invoiceLine = $this->getObjectManager()->find('\Application\Entity\ServiceLine', $id);
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
        $invoiceLine = new \Application\Entity\ServiceLine();
        
        if (isset($data['service_id'])) {
            $invoiceLine->setServiceId($data['service_id']);
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
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG  . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();

        $dataArray = $hydrator->extract($invoiceLine);

        return $dataArray;
    }

    public function deleteEntityByServiceId($serviceId)
    {
        try {
            $numDeleted = $this->getObjectManager()->getConnection()->executeUpdate('DELETE FROM ServiceLine WHERE service_id = ?', array($serviceId));
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
        foreach($lineObjectArr as $serviceId => $objectArr) {
            foreach ($objectArr as $line) {
                $invoiceLine = new \Application\Entity\ServiceLine();

                $itemRef = isset($line->SalesItemLineDetail) ? $line->SalesItemLineDetail->ItemRef : '';

                $invoiceLine->setServiceId($serviceId);
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
    
    public function getEntityByServiceId($serviceId)
    {
        /*try {
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult('\Application\Entity\ServiceLine', 's');
            $rsm->addFieldResult('s', 'id', 'id');
            $rsm->addFieldResult('s', 'service_id', 'service_id');
            $rsm->addFieldResult('s', 'line_num', 'line_num');
            $rsm->addFieldResult('s', 'account_id', 'account_id');
            $rsm->addFieldResult('s', 'description', 'description');
            $rsm->addFieldResult('s', 'amount', 'amount');
            $rsm->addFieldResult('s', 'detail_type', 'detail_type');

            $query = $this->getObjectManager()->createNativeQuery('SELECT * FROM ServiceLine WHERE service_id = ?', $rsm);
            $query->setParameter(1, $serviceId);

            $invoiceLines = $query->getResult();
            
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }*/
        
        try {
            $invoiceLines = $this->getObjectManager()->getRepository('\Application\Entity\ServiceLine')->findBy(array('service_id' => $serviceId));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
            
        //print_r($invoiceLines);

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