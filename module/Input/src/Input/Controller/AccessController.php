<?php
namespace Input\Controller;

use Zend\View\Model\JsonModel;
use lib\exception\PlugintoException;

require_once(__DIR__ . '/../../../../../lib/exception/PlugintoException.php');

class AccessController extends BaseController
{
    public function vendorAuthenticationAction()
    {
        try {
            $userId = $this->validateUser();
        } catch (PlugintoException $ex) {
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
            
            return new JsonModel($result);
        }
        
        $result['data'] = null;
        $returnUrl = base64_encode($this->getServiceLocator()->get('GlobalSettings')->getHostUrl());

        $modelUser = new \Input\Model\User($this->getServiceLocator());
        $modelUser->setUserId($userId);

        try {
            $result['data'] = $modelUser->isUserAuthenticatedByVendor($userId, $returnUrl);
        } catch (PlugintoException $ex) {
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }    
        
        return new JsonModel($result);
    }
    
}