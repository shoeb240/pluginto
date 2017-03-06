<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** 
 * @ORM\Entity 
 * @ORM\Table(name="oauth_access_tokens")
 */
class OauthAccessTokens {
    /**
    * @ORM\Id
    * @ORM\Column(type="string")
    */
    protected $access_token;
    
    /** @ORM\Column(type="string") */
    protected $client_id;

    /** @ORM\Column(type="string") */
    protected $user_id;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $scope;
    
    /** @ORM\Column(type="string") */
    protected $expires;
    
    
    // access_token getter and setter
    public function getAccessToken()
    {
        return $this->access_token;
    }

    public function setAccessToken($value)
    {
        $this->access_token = $value;
    }
    
    // client_id getter and setter
    public function getClientId()
    {
        return $this->client_id;
    }

    public function setClientId($value)
    {
        $this->client_id = $value;
    }
    
    // user_id getter and setter
    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($value)
    {
        $this->user_id = $value;
    }
    
    // scope getter and setter
    public function getScope()
    {
        return $this->scope;
    }

    public function setScope($value)
    {
        $this->scope = $value;
    }
    
    // expires getter and setter
    public function getExpires()
    {
        return $this->expires;
    }

    public function setExpires($value)
    {
        $this->expires = $value;
    }
    
    
    
}