<?php
namespace Input\Model;

use Application\Model\AbstractModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class TaxRate extends AbstractModel
{
    /* TODO: use PHPDoc's DocBlock */

    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\TaxRate', false);
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
        
        try {
            $taxRates = $this->getObjectManager()->getRepository('\Application\Entity\TaxRate')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($taxRates) {
            foreach ($taxRates as $taxRate) {
                $dataArray[] = $hydrator->extract($taxRate);
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
            $taxRates = $this->getObjectManager()->getRepository('\Application\Entity\TaxRate')->findBy(array('user_id' => $this->getUserId()));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        return $taxRates;
    }
    
    public function getEntityById($id)
    {
        try {
            //$taxRate = $this->getObjectManager()->getRepository('\Application\Entity\TaxRate')->findOneBy(array('tax_rate_id' => $id));
            $taxRate = $this->getObjectManager()->find('\Application\Entity\TaxRate', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        if (!$taxRate) {
            throw new PlugintoException(PlugintoException::INVALID_TAXRATE_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_TAXRATE_ID_ERROR_CODE);
        }
        if (!$this->getUserId() || $taxRate->getUserId() != $this->getUserId()) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $dataArray = $hydrator->extract($taxRate);

        return $dataArray;
    }
    
    public function createEntity($data)
    {
        return null;
    }

    public function updateEntity($id, $data) {
        return null;
    }
    
    public function deleteEntity($id)
    {
        return null;
    }
}