<?php
namespace Input\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use lib\exception\PlugintoException;

require_once(__DIR__ . '/../../../../../lib/exception/PlugintoException.php');

class BaseController extends AbstractRestfulController
{
    /* TODO: use PHPDoc's DocBlock */
    protected function tokenSecurityCheck()
    {
        $accessToken = $this->getRequest()->getQuery()->get('access_token', null);
        if (!$accessToken) {
            throw new PlugintoException(PlugintoException::MISSING_ACCESS_TOKEN_ERROR_MSG, 
                                        PlugintoException::MISSING_ACCESS_TOKEN_ERROR_CODE);
        }
        
        $oAuth2Request = \OAuth2\Request::createFromGlobals();
        $oAuth2Request->query['access_token'] = $accessToken;
        $OAuth2Server = $this->getServiceLocator()->get("ZF\OAuth2\Service\OAuth2Server");
        if (!$OAuth2Server->verifyResourceRequest($oAuth2Request)) {
            $response   = $OAuth2Server->getResponse();
            $parameters = $response->getParameters();

            throw new PlugintoException(PlugintoException::INVALID_ACCESS_TOKEN_ERROR_MSG . '. ' . $parameters['error_description'], 
                                        PlugintoException::INVALID_ACCESS_TOKEN_ERROR_CODE);
        }
        
        return $accessToken;
    }
    
    protected function validateUser()
    {
        $userId = null;
        
        $accessToken = $this->tokenSecurityCheck();
        $oauthAccessTokensModel = new \Input\Model\OauthAccessTokens($this->getServiceLocator());
        $userId = $oauthAccessTokensModel->getUserIdByToken($accessToken);
        
        return $userId;
    }

}