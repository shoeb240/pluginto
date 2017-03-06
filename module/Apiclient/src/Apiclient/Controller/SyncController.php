<?php
namespace Apiclient\Controller;

use Apiclient\Controller\ApiclientActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Request;

error_reporting(9);

class SyncController extends ApiclientActionController
{
    public function indexAction()
    {
        $params = array();
        $params['access_token'] = $this->getAccessToken();
        
        return new ViewModel($params);
    }
    
}
