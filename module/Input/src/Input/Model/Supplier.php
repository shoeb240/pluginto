<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class Supplier extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\Supplier', false);
        }

        return $this->_hydrator;
    }

    // used in InputController getList()
    public function fetchAll()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }

        if ($this->getFromVendor()) {
            $supplierArray['user_id'] = $this->getUserId();
            $supplierAccountingModel = $this->getServiceLocator()->get('SupplierModelWrapper')->get($this->getUserAccountingVendor()); //Qb
            $vendorSupplierArr = $supplierAccountingModel->getSupplier($supplierArray);
            $dataArray = array();
            if (!empty($vendorSupplierArr->Results)) {
                foreach($vendorSupplierArr->Results as $eachObj) {
                    $dataArray[] = $supplierAccountingModel->prepareReturnArray($eachObj);
                }
            }
            
            return $dataArray;
        }
        
        try {
            $suppliers = $this->getObjectManager()->getRepository('\Application\Entity\Supplier')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($suppliers) {
            $supplierModel = $this->getServiceLocator()->get('SupplierModelWrapper')->get($this->getUserAccountingVendor()); // Qb
            foreach ($suppliers as $supplier) {
                $eachData = $hydrator->extract($supplier);
                $eachVendorData = $supplierModel->dbVendorFieldFilter($eachData); // New Sage
                $dataArray[] = $eachVendorData;
            }
        }
        
        return $dataArray;
    }

    public function fetchAllObj()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        try {
            $suppliers = $this->getObjectManager()->getRepository('\Application\Entity\Supplier')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        // nned to implement dbVendorFieldFilter type method
        
        return $suppliers;
    }
    
    public function getEntityById($id)
    {
        try {
            $supplier = $this->getObjectManager()->find('\Application\Entity\Supplier', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$supplier) {
            throw new PlugintoException(PlugintoException::INVALID_SUPPLIER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_SUPPLIER_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $supplier->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($supplier);

        $supplierModel = $this->getServiceLocator()->get('SupplierModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $supplierModel->dbVendorFieldFilter($dataArray); // New Sage
                
        return $dataArray;
    }
    
    public function getEntityBySupplierId($id)
    {
        try {
            $supplier = $this->getObjectManager()->getRepository('\Application\Entity\Supplier')->findOneBy(array('vendor_supplier_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$supplier) {
            throw new PlugintoException(PlugintoException::INVALID_SUPPLIER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_SUPPLIER_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $supplier->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($supplier);

        $supplierModel = $this->getServiceLocator()->get('SupplierModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $supplierModel->dbVendorFieldFilter($dataArray); // New Sage
        
        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG . 'xxx', 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();

        $supplierModel = $this->getServiceLocator()->get('SupplierModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $confirmationArray = $supplierModel->createSupplier($data);
        
        $supplier = new \Application\Entity\Supplier();

        if (isset($data['user_id'])) {
            $supplier->setUserId($data['user_id']);
        }
        if (isset($confirmationArray['vendor_supplier_id'])) {
            $supplier->setVendorSupplierId($confirmationArray['vendor_supplier_id']);
        }
        if (isset($confirmationArray['vat_id'])) {
            $supplier->setVatId($confirmationArray['vat_id']);
        }
        if (isset($confirmationArray['supplier_name'])) {
            $supplier->setSupplierName($confirmationArray['supplier_name']);
        }
        if (isset($confirmationArray['display_name'])) {
            $supplier->setDisplayName($confirmationArray['display_name']);
        }
        if (isset($confirmationArray['name'])) {
            $supplier->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['surname'])) {
            $supplier->setSurname($confirmationArray['surname']);
        }
        if (isset($confirmationArray['address1'])) {
            $supplier->setAddress1($confirmationArray['address1']);
        }
        if (isset($confirmationArray['address2'])) {
            $supplier->setAddress2($confirmationArray['address2']);
        }
        if (isset($confirmationArray['city'])) {
            $supplier->setCity($confirmationArray['city']);
        }
        if (isset($confirmationArray['postcode'])) {
            $supplier->setPostcode($confirmationArray['postcode']);
        }
        if (isset($confirmationArray['country'])) {
            $supplier->setCountry($confirmationArray['country']);
        }
        if (isset($data['supplier_type'])) {
            $supplier->setSupplierType($data['supplier_type']);
        }
        $supplier->setSyncToken($confirmationArray['sync_token']);

        try {
            $this->getObjectManager()->persist($supplier);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }            
        
        $hydrator = $this->getHydrator();
        $supplierArray = $hydrator->extract($supplier);
        
        $supplierArray = $supplierModel->dbVendorFieldFilter($supplierArray); // New Sage
        
        return $supplierArray;
    }

    public function updateEntity($id, $data)
    {
        try {
            $supplier = $this->getObjectManager()->find('\Application\Entity\Supplier', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$supplier) {
            throw new PlugintoException(PlugintoException::INVALID_SUPPLIER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_SUPPLIER_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $supplier->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $supplierModel = $this->getServiceLocator()->get('SupplierModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $supplierModel->updateSupplier($supplier, $data);
        
        if (isset($confirmationArray['vat_id'])) {
            $supplier->setVatId($confirmationArray['vat_id']);
        }
        if (isset($confirmationArray['supplier_name'])) {
            $supplier->setSupplierName($confirmationArray['supplier_name']);
        }
        if (isset($confirmationArray['display_name'])) {
            $supplier->setDisplayName($confirmationArray['display_name']);
        }
        if (isset($confirmationArray['name'])) {
            $supplier->setName($confirmationArray['name']);
        }
        if (isset($confirmationArray['surname'])) {
            $supplier->setSurname($confirmationArray['surname']);
        }
        if (isset($confirmationArray['address1'])) {
            $supplier->setAddress1($confirmationArray['address1']);
        }
        if (isset($confirmationArray['address2'])) {
            $supplier->setAddress2($confirmationArray['address2']);
        }
        if (isset($confirmationArray['city'])) {
            $supplier->setCity($confirmationArray['city']);
        }
        if (isset($confirmationArray['postcode'])) {
            $supplier->setPostcode($confirmationArray['postcode']);
        }
        if (isset($confirmationArray['country'])) {
            $supplier->setCountry($confirmationArray['country']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $supplier->setSyncToken($confirmationArray['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($supplier);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $supplierArray = $hydrator->extract($supplier);
        
        $supplierArray = $supplierModel->dbVendorFieldFilter($supplierArray); // New Sage
        
        return $supplierArray;
    }

    public function deleteEntity($id)
    {
        try {
            $supplier = $this->getObjectManager()->find('\Application\Entity\Supplier', $id);
            if (!$supplier) {
                throw new PlugintoException(PlugintoException::INVALID_SUPPLIER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_SUPPLIER_ID_ERROR_CODE);
            }
            if (!$this->getUserId() || $supplier->getUserId() != $this->getUserId()) {
                throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_USER_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $supplierArray = $hydrator->extract($supplier);
            
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($supplier);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $supplierModel = $this->getServiceLocator()->get('SupplierModelWrapper')->get($this->getUserAccountingVendor());
        try {
            $confirmationArray = $supplierModel->deleteSupplier($supplier, $supplierArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;
    }
}