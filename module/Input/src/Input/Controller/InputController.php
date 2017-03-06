<?php
namespace Input\Controller;

use Zend\View\Model\JsonModel;
use lib\exception\PlugintoException;

require_once(__DIR__ . '/../../../../../lib/exception/PlugintoException.php');

//error_reporting(9);

class InputController extends BaseController
{
    /* TODO: use PHPDoc's DocBlock */
    protected $_model;
    protected $_modelName;

    // used for unittest
    public function __construct($model = null, $validator = null)
    {
        $this->_model = $model;
    }
    
    protected function _setModelName($modelName)
    {
        $this->_modelName = $modelName;
    }
    
    protected function _getModelName()
    {
        if (!$this->_modelName) {
            $this->prepareModel();
        }
        
        return $this->_modelName;
    }
    
    protected function _setModel($model)
    {
        $this->_model = $model;
    }
    
    protected function _getModel()
    {
        if (!$this->_model) {
            $this->prepareModel();
        }
        
        return $this->_model;
    }
    
    private function prepareModel()
    {
        $inputType = $this->getEvent()->getRouteMatch()->getParam('input-type');
        $inputType = str_replace('-', ' ', $inputType);
        $inputType = ucwords($inputType);
        $inputType = str_replace(' ', '', $inputType);
        $inputType= ucfirst($inputType);
        $modelName = '';
        if (class_exists("\\Input\\Model\\$inputType")) {
            $modelName = "\\Input\\Model\\$inputType";
        } else if (class_exists("\\Application\\Model\\$inputType")) {
            $modelName = "\\Application\\Model\\$inputType";
        }
        if ($modelName) {
            $model = new $modelName($this->getServiceLocator());
            $this->_setModel($model);
            $this->_setModelName($modelName);
        } else {
            throw new PlugintoException(PlugintoException::INVALID_METHOD_CALL_ERROR_MSG, 
                                        PlugintoException::INVALID_METHOD_CALL_ERROR_CODE);
        }
    }
    
    private function userDetectionAndSetup()
    {
        $userId = $this->validateUser();
                
        $this->_getModel()->setUserId($userId);

        $userModel = $this->getServiceLocator()->get("UserModel");
        $userArr = $userModel->getEntityById($userId);
        $this->_getModel()->setUserAccountingVendor($userArr['accounting_vendor']);
    }
    
    public function getList()
    {
        $headers = $this->request->getHeaders()->toArray();
        $result['data'] = null;
        
        // Actually, User getList is accessible only for super admin
        // for for now it is accessible by all
        if ($this->_getModelName() != '\Input\Model\User') {
            try {
                $this->userDetectionAndSetup();
            } catch (PlugintoException $ex) {
                $result['error_code'] = $ex->getCode();
                $result['error_msg'] = $ex->getMessage();

                return new JsonModel($result);
            }
        }
        
        // initiating log
        $log = $this->getServiceLocator()->get("LogModel");
        $log->setUserId($this->_getModel()->getUserId());
        $log->setMethodName('fetchAll - ' . $this->_getModelName());

        if (!$this->request->isGet()) {
            $result['error_code'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_MSG;
        }
        
        if (!empty($result['error_code']) || !empty($result['error_msg'])) {
            $log->setMessage('Response', serialize($result));
            return new JsonModel($result);
        }
        
        // get all entities
        try {
            $result['data'] = $this->_getModel()->fetchAll();
        } catch (PlugintoException $ex) {
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }
        
        $log->setMessage('Response', serialize($result));
        $log->writeLog('debug');
        
        return new JsonModel($result);
    }

    public function get($id)    
    {
        $headers = $this->request->getHeaders()->toArray();
        $result['data'] = null;
        
        try {
            $this->userDetectionAndSetup();
        } catch (PlugintoException $ex) {
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
            
            return new JsonModel($result);
        }
        
        // initiating log
        $log = $this->getServiceLocator()->get("LogModel");
        $log->setUserId($this->_getModel()->getUserId());
        $log->setEntityId($id);
        $log->setMethodName('getEntityById - ' . $this->_getModelName());

        if (!$this->request->isGet()) {
            $result['error_code'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_MSG;
        }
        
        if (!empty($result['error_code']) || !empty($result['error_msg'])) {
            $log->setMessage('Response', serialize($result));
            return new JsonModel($result);
        }

        // get entity by id
        try {
            $result['data'] = $this->_getModel()->getEntityById($id);
        } catch (PlugintoException $ex) {
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }
        
        $log->setMessage('Response', serialize($result));
        $log->writeLog('debug');
        
        return new JsonModel($result);
    }

    public function create($data)
    {
        $headers = $this->request->getHeaders()->toArray();
        $result['data'] = null;
        
        if ($this->_getModelName() != '\Input\Model\User') {
            try {
                $this->userDetectionAndSetup();
            } catch (PlugintoException $ex) {
                $result['error_code'] = $ex->getCode();
                $result['error_msg'] = $ex->getMessage();

                return new JsonModel($result);
            }
        }
        
        // initiating log
        $log = $this->getServiceLocator()->get("LogModel");
        $log->setUserId($this->_getModel()->getUserId());
        $log->setMethodName('createEntity - ' . $this->_getModelName());
        $log->setMessage('Request Data', serialize($data));
        
        if (!$this->request->isPost()) {
            $result['error_code'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_MSG;
        } else if ($headers['Accept'] != 'application/json') {
            $result['error_code'] = PlugintoException::INVALID_DATA_FORMAT_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_DATA_FORMAT_ERROR_MSG;
        } else if (!$data || !is_array($data)) {
            $result['error_code'] = PlugintoException::INVALID_DATA_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_DATA_ERROR_MSG;            
        }
        
        if (!empty($result['error_code']) || !empty($result['error_msg'])) {
            $log->setMessage('Response', serialize($result));
            return new JsonModel($result);
        }
        
        // receive and store entity
        // TODO: put some validation here
        try {
            $result['data'] = $this->_getModel()->createEntity($data);
        } catch (\Exception $e) {
            $result['error_code'] = $e->getCode();
            $result['error_msg'] = $e->getMessage();
        }
    
        $log->setMessage('Response', serialize($result));
        $log->writeLog('debug');
        
        return new JsonModel($result);
    }

    public function update($id, $data)
    {
        $headers = $this->request->getHeaders()->toArray();
        $result['data'] = null;
        
        try {
            $this->userDetectionAndSetup();
        } catch (PlugintoException $ex) {
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
            
            return new JsonModel($result);
        }
        
        // initiating log
        $log = $this->getServiceLocator()->get("LogModel");
        $log->setUserId($this->_getModel()->getUserId());
        $log->setEntityId($id);
        $log->setMethodName('updateEntity - ' . $this->_getModelName());
        $log->setMessage('Request Data', serialize($data));
        
        if (!$this->request->isPut()) {
            $result['error_code'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_MSG;
        } else if ($headers['Accept'] != 'application/json') {
            $result['error_code'] = PlugintoException::INVALID_DATA_FORMAT_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_DATA_FORMAT_ERROR_MSG;
        } else if (!$data || !is_array($data)) {
            $result['error_code'] = PlugintoException::INVALID_DATA_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_DATA_ERROR_MSG;            
        }
        
        if (!empty($result['error_code']) || !empty($result['error_msg'])) {
            $log->setMessage('Response', serialize($result));
            return new JsonModel($result);
        }
        
        // update entity
        try {
            $result['data'] = $this->_getModel()->updateEntity($id, $data);
        } catch (\Exception $e) {
            $result['error_code'] = $e->getCode();
            $result['error_msg'] = $e->getMessage();
        }
        
        $log->setMessage('Response', serialize($result));
        $log->writeLog('debug');
        
        return new JsonModel($result);
    }

    public function delete($id)
    {
        $headers = $this->request->getHeaders()->toArray();
        $result['data'] = null;
        
        try {
            $this->userDetectionAndSetup();
        } catch (PlugintoException $ex) {
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
            
            return new JsonModel($result);
        }
        
        // initiating log
        $log = $this->getServiceLocator()->get("LogModel");
        $log->setUserId($this->_getModel()->getUserId());
        $log->setEntityId($id);
        $log->setMethodName('deleteEntity - ' . $this->_getModelName());
        
        if (!$this->request->isDelete()) {
            $result['error_code'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_REQUEST_METHOD_ERROR_MSG;
        } else if ($headers['Accept'] != 'application/json') {
            $result['error_code'] = PlugintoException::INVALID_DATA_FORMAT_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_DATA_FORMAT_ERROR_MSG;
        } else if (!$id) {
            $result['error_code'] = PlugintoException::INVALID_DATA_ERROR_CODE;
            $result['error_msg'] = PlugintoException::INVALID_DATA_ERROR_MSG;            
        }

        if (!empty($result['error_code']) || !empty($result['error_msg'])) {
            $log->setMessage('Response', serialize($result));
            return new JsonModel($result);
        }
        
        // delete entity
        try {
            $result['data'] = $this->_getModel()->deleteEntity($id);
        } catch (PlugintoException $ex) {
            $result['error_code'] = $ex->getCode();
            $result['error_msg'] = $ex->getMessage();
        }
        
        $log->setMessage('Response', serialize($result));
        $log->writeLog('debug');
        
        return new JsonModel($result);
    }
    
}