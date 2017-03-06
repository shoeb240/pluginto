<?php
namespace Input\Model;

use Application\Model\BaseModel;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use lib\exception\PlugintoException;

class OauthAccessTokens extends BaseModel
{
    /* TODO: use PHPDoc's DocBlock */
    protected function getHydrator()
    {
        if (!$this->_hydrator) {
            $objectManager = $this->getObjectManager();
            $this->_hydrator = new DoctrineHydrator($objectManager, 'Application\Entity\OauthAccessTokens', false);
        }

        return $this->_hydrator;
    }
    
    public function getUserIdByToken($token)
    {
        try {
            $user = $this->getObjectManager()->getRepository('\Application\Entity\OauthAccessTokens')->findOneBy(array('access_token' => $token));
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }
        
        if (!$user) {
            throw new PlugintoException(PlugintoException::INVALID_USER_ID_ERROR_MSG, 
                                        PlugintoException::INVALID_USER_ID_ERROR_CODE);
        }
        
        $hydrator = $this->getHydrator();
        $userArr = $hydrator->extract($user);
        $userId = $user->getUserId();

        return $userId;
    }

}