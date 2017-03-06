<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
/** 
 * @ORM\Entity 
 * @ORM\Table(name="oauth_scopes")
 */
class OauthScopes {
    /**
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    * @ORM\Column(type="integer")
    */
    protected $id;
    
    /** @ORM\Column(type="string") */
    protected $type;

    /** @ORM\Column(type="string", nullable=true) */
    protected $scope;
    
    /** @ORM\Column(type="string", nullable=true) */
    protected $client_id;
    
    /** @ORM\Column(type="integer", nullable=true) */
    protected $is_default;
    
}