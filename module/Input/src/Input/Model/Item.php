<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class Item extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\Item', false);
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
            $itemArray['user_id'] = $this->getUserId();
            $itemAccountingModel = $this->getServiceLocator()->get('ItemModelWrapper')->get($this->getUserAccountingVendor()); //Qb
            $vendorItemArr = $itemAccountingModel->getItem($itemArray);
            $dataArray = array();
            if (!empty($vendorItemArr->Results)) {
                foreach($vendorItemArr->Results as $eachObj) {
                    $dataArray[] = $itemAccountingModel->prepareReturnArray($eachObj);
                }
            }
            
            return $dataArray;
        }

        try {
            $items = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($items) {
            $itemModel = $this->getServiceLocator()->get('ItemModelWrapper')->get($this->getUserAccountingVendor()); // Qb
            foreach ($items as $item) {
                $eachData = $hydrator->extract($item);
                $eachVendorData = $itemModel->dbVendorFieldFilter($eachData); // New Sage
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
            $items = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        // nned to implement dbVendorFieldFilter type method
        
        return $items;
    }
    
    public function getAllVendorItemId()
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                      PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        try {
            $items = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $dataArray = array();
        if ($items) {
            foreach ($items as $item) {
                $dataArray[$item->getId()] = $item->getVendorItemId();
            }
        }
        
        return $dataArray;
    }
    
    public function getEntityById($id)
    {
        try {
            $item = $this->getObjectManager()->find('\Application\Entity\Item', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$item) {
            throw new PlugintoException(PlugintoException::INVALID_ITEM_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_ITEM_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $item->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($item);

        $itemModel = $this->getServiceLocator()->get('ItemModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $itemModel->dbVendorFieldFilter($dataArray); // New Sage
                
        return $dataArray;
    }
    
    public function getEntityBySupplierId($id)
    {
        try {
            $item = $this->getObjectManager()->getRepository('\Application\Entity\Item')->findOneBy(array('vendor_item_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$item) {
            throw new PlugintoException(PlugintoException::INVALID_ITEM_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_ITEM_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $item->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($item);

        $itemModel = $this->getServiceLocator()->get('ItemModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $dataArray = $itemModel->dbVendorFieldFilter($dataArray); // New Sage
        
        return $dataArray;
    }

    public function createEntity($data)
    {
        if (!$this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
//        if (empty($data['item_type'])) {
//            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ITEM_TYPE_ERROR_MSG, 
//                                        PlugintoException::MISSING_REQUIRED_FIELD_ITEM_TYPE_ERROR_CODE);
//        }

        $itemModel = $this->getServiceLocator()->get('ItemModelWrapper')->get($this->getUserAccountingVendor()); // Qb
        $confirmationArray = $itemModel->createItem($data);
        
        $item = new \Application\Entity\Item();

        if (isset($data['user_id'])) {
            $item->setUserId($data['user_id']);
        }
        if (isset($confirmationArray['vendor_item_id'])) {
            $item->setVendorItemId($confirmationArray['vendor_item_id']);
        }
        if (isset($confirmationArray['category_id'])) {
            $item->setCategoryId($confirmationArray['category_id']);
        }
        if (isset($confirmationArray['item_name'])) {
            $item->setItemName($confirmationArray['item_name']);
        }
        if (isset($confirmationArray['item_type'])) {
            $item->setItemType($confirmationArray['item_type']);
        }
        if (isset($confirmationArray['income_account_id'])) {
            $item->setIncomeAccountId($confirmationArray['income_account_id']);
        }
        if (isset($confirmationArray['expense_account_id'])) {
            $item->setExpenseAccountId($confirmationArray['expense_account_id']);
        }
        if (isset($confirmationArray['description'])) {
            $item->setDescription($confirmationArray['description']);
        }
        if (isset($confirmationArray['price'])) {
            $item->setPrice($confirmationArray['price']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $item->setSyncToken($confirmationArray['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($item);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }            
        
        $hydrator = $this->getHydrator();
        $itemArray = $hydrator->extract($item);
        
        $itemArray = $itemModel->dbVendorFieldFilter($itemArray); // New Sage
        
        return $itemArray;
    }

    public function updateEntity($id, $data)
    {
        try {
            $item = $this->getObjectManager()->find('\Application\Entity\Item', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$item) {
            throw new PlugintoException(PlugintoException::INVALID_ITEM_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_ITEM_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $item->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        $data['user_id'] = $this->getUserId();
        
        $itemModel = $this->getServiceLocator()->get('ItemModelWrapper')->get($this->getUserAccountingVendor());
        $confirmationArray = $itemModel->updateItem($item, $data);
        
        if (isset($confirmationArray['category_id'])) {
            $item->setVatId($confirmationArray['category_id']);
        }
        if (isset($confirmationArray['item_name'])) {
            $item->setItemName($confirmationArray['item_name']);
        }
        if (isset($confirmationArray['item_type'])) {
            $item->setItemType($confirmationArray['item_type']);
        }
        if (isset($confirmationArray['income_account_id'])) {
            $item->setIncomeAccountId($confirmationArray['income_account_id']);
        }
        if (isset($confirmationArray['expense_account_id'])) {
            $item->setExpenseAccountId($confirmationArray['expense_account_id']);
        }
        if (isset($confirmationArray['description'])) {
            $item->setDescription($confirmationArray['description']);
        }
        if (isset($confirmationArray['price'])) {
            $item->setPrice($confirmationArray['price']);
        }
        if (isset($confirmationArray['sync_token'])) {
            $item->setSyncToken($confirmationArray['sync_token']);
        }

        try {
            $this->getObjectManager()->persist($item);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            // TODO: implement #36
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $itemArray = $hydrator->extract($item);
        
        $itemArray = $itemModel->dbVendorFieldFilter($itemArray); // New Sage
        
        return $itemArray;
    }

    public function deleteEntity($id)
    {
        try {
            $item = $this->getObjectManager()->find('\Application\Entity\Item', $id);
            if (!$item) {
                throw new PlugintoException(PlugintoException::INVALID_ITEM_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_ITEM_ID_ERROR_CODE);
            }
            if (!$this->getUserId() || $item->getUserId() != $this->getUserId()) {
                throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                            PlugintoException::INVALID_USER_ID_ERROR_CODE);
            }
            
            $hydrator = $this->getHydrator();
            $itemArray = $hydrator->extract($item);
            
            // Db transaction begins
            $this->getObjectManager()->beginTransaction();
            
            $this->getObjectManager()->remove($item);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        $itemModel = $this->getServiceLocator()->get('ItemModelWrapper')->get($this->getUserAccountingVendor());
        try {
            $confirmationArray = $itemModel->deleteItem($item, $itemArray);
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException($e->getMessage(), $e->getCode());
        }
        
        // Db transaction commits
        $this->getObjectManager()->commit();
        
        return $confirmationArray;
    }
    
    public function createAll($userId, $objectArr)
    {
        if (empty($userId)) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_USER_ID_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_USER_ID_ERROR_CODE);
        }

        $itemArray = array();
                
        foreach($objectArr as $ippItem) {
            if (empty($ippItem->Id)) {
                continue;
            }
            
            $item = new \Application\Entity\Item();

            if (isset($ippItem->Id)) {
                $item->setVendorItemId($ippItem->Id);
            }
            if (isset($userId)) {
                $item->setUserId($userId);
            }
            if (isset($ippItem->Name)) {
                $item->setItemName($ippItem->Name);
            }
            if (isset($ippItem->Type)) {
                $item->setItemType($ippItem->Type);
            }
            if (isset($ippItem->IncomeAccountRef)) {
                $item->setIncomeAccountId($ippItem->IncomeAccountRef);
            }
            if (isset($ippItem->ExpenseAccountRef)) {
                $item->setExpenseAccountId($ippItem->ExpenseAccountRef);
            }
            if (isset($ippItem->UnitPrice)) {
                $item->setPrice($ippItem->UnitPrice);
            }
            $item->setSyncToken($ippItem->SyncToken);
            
            $this->getObjectManager()->persist($item);
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
    
    public function updateAll($objectArr)
    {
        if (!$objectArr) return false;
        
        foreach($objectArr as $item) {
            $this->getObjectManager()->persist($item);
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
    
    public function deleteAll($objectArr)
    {
        if (!$objectArr) return false;
        
        foreach($objectArr as $item) {
            $this->getObjectManager()->remove($item);
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
}