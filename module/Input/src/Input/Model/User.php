<?php
namespace Input\Model;

use Application\Model\BaseModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class User extends BaseModel
{
    /* TODO: use PHPDoc's DocBlock */
    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\User', false);
        }

        return $this->_hydrator;
    }
    
    // used in InputController getList()
    public function fetchAll()
    {
        try {
            $users = $this->getObjectManager()->getRepository('\Application\Entity\User')->findAll();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();
        $dataArray = array();
        if ($users) {
            foreach ($users as $user) {
                $dataArray[] = $hydrator->extract($user);
            }
        }
        
        return $dataArray;
    }

    public function getEntityById($id)
    {
        try {
            $user = $this->getObjectManager()->find('\Application\Entity\User', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        if (!$user) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
//        if (!$this->getUserId() || $user->getId() != $this->getUserId()) {
//            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
//                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
//        }

        $hydrator = $this->getHydrator();
        $userArr = $hydrator->extract($user);
        $userArr['property_list'] = $user->getPropertyList();
        $userArr['default_entity_list'] = $user->getDefaultEntityList();

        return $userArr;
    }

    public function createEntity($data)
    {
        $user = new \Application\Entity\User();
        
        if (empty($data['accounting_vendor'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNTING_VENDOR_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_ACCOUNTING_VENDOR_ERROR_CODE);
        }
        if (empty($data['user_login'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_USER_LOGIN_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_USER_LOGIN_ERROR_CODE);
        }
        if (empty($data['user_password'])) {
            throw new PlugintoException(PlugintoException::MISSING_REQUIRED_FIELD_USER_PASSWORD_ERROR_MSG, 
                                        PlugintoException::MISSING_REQUIRED_FIELD_USER_PASSWORD_ERROR_CODE);
        }

        if (isset($data['property_list'])) {
            $user->setPropertyList($data['property_list']);
        }
        $user->setAccountingVendor($data['accounting_vendor']);
        $user->setUserLogin($data['user_login']);

        // Db transaction begins
        $this->getObjectManager()->beginTransaction();
        try {
            $this->getObjectManager()->persist($user);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();
        $userArr = $hydrator->extract($user);
        $userArr['property_list'] = $user->getPropertyList();
        $userArr['default_entity_list'] = $user->getDefaultEntityList();
        
        $password = password_hash($data['user_password'], PASSWORD_DEFAULT, ['cost' => 14]);
        $oauthClients = new \Application\Entity\OauthClients();
        $oauthClients->setClientId($data['user_login']);
        $oauthClients->setClientSecret($password);
        $oauthClients->setUserId($userArr['id']);
        $oauthClients->setRedirectUri('');
        try {
            $this->getObjectManager()->persist($oauthClients);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        } 
        
        // Db transaction commits
        $this->getObjectManager()->commit();

        return $userArr;
    }

    public function updateEntity($id, $data)
    {
        try {
            $user = $this->getObjectManager()->find('\Application\Entity\User', $id);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        if (!$user) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
//        if (!$this->getUserId() || $user->getId() != $this->getUserId()) {
//            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
//                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
//        }
        
        if (isset($data['property_list'])) {
            $userPropertyList = $user->getPropertyList();
            if (!is_array($data['property_list'])) {
                $data['property_list'] = $this->getPropertyList($data['property_list']);
            }
            $propertyList = array_merge($userPropertyList, $data['property_list']);
            $user->setPropertyList($propertyList);
        }
        if (isset($data['user_login'])) {
            $user->setUserLogin($data['user_login']);
        }
        if (isset($data['default_entity_list'])) {
            $userDefaultEntityList = $user->getDefaultEntityList();
            if (!is_array($data['default_entity_list'])) {
                $data['property_list'] = explode(',', $data['default_entity_list']);
            }
            $defaultEntityList = array_merge($userDefaultEntityList, $data['default_entity_list']);
            $user->setDefaultEntityList($defaultEntityList);
        }

        try {
            $this->getObjectManager()->persist($user);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    

        $hydrator = $this->getHydrator();
        $userArr = $hydrator->extract($user);
        $userArr['property_list'] = $user->getPropertyList();
        $userArr['default_entity_list'] = $user->getDefaultEntityList();
        
        try {
            $oauthClients = $this->getObjectManager()->getRepository('\Application\Entity\OauthClients')->findOneBy(array('user_id' => $id));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        $oauthClients->setClientId($user->getUserLogin());
        if (!empty($data['user_password'])) {
            $password = password_hash($data['user_password'], PASSWORD_DEFAULT, ['cost' => 14]);
            $oauthClients->setClientSecret($password);
        }
        try {
            $this->getObjectManager()->persist($oauthClients);
            $this->getObjectManager()->flush();
        } catch(\Exception $e) {
            $this->getObjectManager()->rollback();
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG . $e->getMessage(), 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }

        return $userArr;
    }

    public function deleteEntity($id)
    {
        try {
            $user = $this->getObjectManager()->find('\Application\Entity\User', $id);

            if ($user) {
//                if (!$this->getUserId() || $user->getId() != $this->getUserId()) {
//                    throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
//                                                PlugintoException::INVALID_USER_ID_ERROR_CODE);
//                }
                $this->getObjectManager()->remove($user);
                $this->getObjectManager()->flush();

                return true;
            }
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                    PlugintoException::INVALID_USER_ID_ERROR_CODE);
    }
    
    private function getPropertyList($propertyList)
    {
        $stArr = explode('~~', $propertyList);
        $propertyArr = array();
        foreach($stArr as $st) {
            $tmpArr = explode('=', $st);
            if (isset($tmpArr[1])) {
                $propertyArr[trim($tmpArr[0])] = trim($tmpArr[1]);
            }
        }
        return $propertyArr;
    }
    
    public function isUserAuthenticatedByVendor($id, $returnUrl)
    {
        $userModel = $this->getServiceLocator()->get("UserModel");
        $userArr = $userModel->getEntityById($id);
        
        $accounting = $this->getServiceLocator()->get('AccountingModelWrapper')->get($userArr['accounting_vendor']);
        
        if (empty($userArr['accounting_vendor'])) {
            throw new PlugintoException(PlugintoException::ACCOUNT_VENDOR_NOT_SET_ERROR_MSG, 
                                        PlugintoException::ACCOUNT_VENDOR_NOT_SET_ERROR_CODE);
        } else if (!in_array($userArr['accounting_vendor'], array('Qb', 'Sage'))) {
            throw new PlugintoException(PlugintoException::INVALID_ACCOUNT_VENDOR_SET_ERROR_MSG, 
                                        PlugintoException::INVALID_ACCOUNT_VENDOR_SET_ERROR_CODE);
        } else if (empty($returnUrl)) {
            throw new PlugintoException(PlugintoException::BASE64_ENCODED_URL_MISSING_ERROR_MSG, 
                                        PlugintoException::BASE64_ENCODED_URL_MISSING_ERROR_CODE);
        } else if( !$accounting->isAuthenticated($userArr) ) {
            $accounting->notAuthenticated($userModel, $userArr, $id, $returnUrl);
        }
        
        return true;
    }

}