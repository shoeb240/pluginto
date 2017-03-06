<?php
namespace Application\Model;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

abstract class AbstractModel extends BaseModel
{
    /* TODO: use PHPDoc's DocBlock */
    protected $_entity;

    public function setHydrator(DoctrineHydrator $hydrator)
    {
        $this->_hydrator = $hydrator;
    }

    abstract protected function getHydrator();
    
    // used for unittest
    public function setEntity($entity)
    {
        $this->_entity = $entity;
    }

    abstract public function fetchAll();

    abstract public function getEntityById($id);

    abstract public function createEntity($data);

    abstract public function updateEntity($id, $data);

    abstract public function deleteEntity($id);

}