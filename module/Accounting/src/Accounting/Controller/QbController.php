<?php
namespace Accounting\Controller;

use Zend\Session\Container;
use Zend\Mvc\Controller\AbstractActionController;

require_once(__DIR__ . '/../../../../../lib/exception/PlugintoException.php');

define('OAUTH_REQUEST_URL', 'https://oauth.intuit.com/oauth/v1/get_request_token');
define('OAUTH_ACCESS_URL', 'https://oauth.intuit.com/oauth/v1/get_access_token');
define('OAUTH_AUTHORISE_URL', 'https://appcenter.intuit.com/Connect/Begin');


class QbController extends AbstractActionController
{
    private $_container;
    private $_oauthConsumerKey;
    private $_oauthConsumerSecret;
    
    protected function getConsumerKey()
    {
        return $this->getServiceLocator()->get('GlobalSettings')->getConsumerKey();
    }
    
    protected function getConsumerSecret()
    {
        return $this->getServiceLocator()->get('GlobalSettings')->getConsumerSecret();
    }

    private function getContainer()
    {
        if (!$this->_container) {
            $this->_container = new Container('qb');
        }
        
        return $this->_container;
    }
    
    public function oauthAction()
    {
        $id = $this->params('id', '');
	$security_token = $this->params('token', '');

        if (empty($id)) {
            die("id parameter missing");
        }
	$userModel = $this->getServiceLocator()->get("UserModel");
        //$userModel->setUserId($id);
        $userArr = $userModel->getEntityById($id);
        try {
            $oauth = new \OAuth($this->getConsumerKey(), 
                                $this->getConsumerSecret(), 
                                OAUTH_SIG_METHOD_HMACSHA1, 
                                OAUTH_AUTH_TYPE_URI);
            $oauth->enableDebug();
            
            if (!isset( $_GET['oauth_token'] )) {
		if (empty($userArr['property_list']['_pi_auth_token']) 
                    || $security_token != $userArr['property_list']['_pi_auth_token']
                ) {
                    echo $security_token .'!='. $userArr['property_list']['_pi_auth_token'];
                    die("security");
	    	}

                // step 1: get request token from Intuit
		$settings = $this->getServiceLocator()->get("GlobalSettings");
		$callback_url = $settings->getHostUrl(). $this->getRequest()->getRequestUri();
                $request_token = $oauth->getRequestToken(OAUTH_REQUEST_URL, $callback_url);
                $this->getContainer()->secret = $request_token['oauth_token_secret'];
                $userArr['property_list']['_pi_auth_token'] = '';
		$userArr = $userModel->updateEntity($userArr['id'],$userArr);
		if (empty($userArr)) {
		    die("internal error");
		}
                // step 2: send user to intuit to authorize 
                header('Location: '. OAUTH_AUTHORISE_URL .'?oauth_token='.$request_token['oauth_token']);
                exit();
            }
            
            if ( isset($_GET['oauth_token']) and isset($_GET['oauth_verifier'])) {
                // step 3: request a access token from Intuit
                $oauth->setToken($_GET['oauth_token'], $this->getContainer()->secret);
		$this->getContainer()->secret = '';
                $access_token = $oauth->getAccessToken( OAUTH_ACCESS_URL );

                $token = serialize( $access_token );
                $realmId = $_REQUEST['realmId'];  // realmId is legacy for customerId
                $dataSource = $_REQUEST['dataSource'];
                
                $this->updatePropertyList($id, $token, $realmId, $dataSource);

		header('Location: '. base64_decode($this->params('returnurl')));
                exit();
            }
        } catch(OAuthException $e) {
            echo '<pre>';
            print_r($e);
            echo '</pre>';
        }
    }
    
    
    private function updatePropertyList($id, $token, $realmId, $dataSource)
    {
        $userModel = $this->getServiceLocator()->get("UserModel");
        //$userModel->setUserId($id);
        $userArray = $userModel->getEntityById($id);

        $data['property_list'] = array('token' => $token,
                                       'token_exp_date' => date("Y-m-d",strtotime("+180 days")),
                                       'realm_id' => $realmId,
                                       'data_source' => $dataSource);
        if (empty($userArray['id'])) {
            $data['id'] = $id;
            $data['accounting_vendor'] = 'Qb';
            $userArray = $userModel->createEntity($data);
        } else {
            $data['property_list'] = array_merge($userArray['property_list'], $data['property_list']);
            $userArray = $userModel->updateEntity($id, $data);
        }
    }
    
    
}