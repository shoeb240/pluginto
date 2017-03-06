<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** 
 * @ORM\Entity 
 * @ORM\Table(name="oauth_clients")
 */
class OauthClients {
    /**
    * @ORM\Id
    * @ORM\Column(type="string")
    */
    protected $client_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $client_secret;
    
    /** @ORM\Column(type="string") */
    protected $redirect_uri;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $grant_types;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $scope;
    
    /** @ORM\Column(type="string") */
    protected $user_id;
    
    // client_secret getter and setter
    public function getClientId()
    {
        return $this->client_id;
    }

    public function setClientId($value)
    {
        $this->client_id = $value;
    }
    
    // client_secret getter and setter
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    public function setClientSecret($value)
    {
        $this->client_secret = $value;
    }
    
    // redirect_uri getter and setter
    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    public function setRedirectUri($value)
    {
        $this->redirect_uri = $value;
    }
    
    // grant_types getter and setter
    public function getGrantTypes()
    {
        return $this->grant_types;
    }

    public function setGrantTypes($value)
    {
        $this->grant_types = $value;
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
    
    // user_id getter and setter
    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($value)
    {
        $this->user_id = $value;
    }
    
}