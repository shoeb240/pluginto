<?php
namespace Input\Model;

use Application\Model\BaseModel;
use lib\exception\PlugintoException;

class Validator extends BaseModel
{
    /* TODO: use PHPDoc's DocBlock */

    /*public function validateCompany($data)
    {
        if (!isset($data['company_id'])) {
            return false;
        }
        
        try {
            $company = $this->getObjectManager()->find('\Application\Entity\Company', $data['company_id']);
        } catch(\Exception $e) {
            throw new PlugintoException(PlugintoException::INTERNAL_SERVER_ERROR_MSG, 
                                        PlugintoException::INTERNAL_SERVER_ERROR_CODE);
        }    
        
        if (!$company) {
            return false;
        }
        
        return true;
    }*/

}