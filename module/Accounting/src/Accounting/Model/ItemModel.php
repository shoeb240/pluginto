<?php
namespace Accounting\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class ItemModel implements ServiceLocatorAwareInterface
{
    private $serviceLocator;

    abstract protected function getItem($userId);
    
    abstract protected function createItem($itemArray);
    
    abstract protected function updateItem(\Application\Entity\Item $item, $itemArray);
    
    abstract protected function deleteItem(\Application\Entity\Item $item, $itemArray);
    
    /*abstract protected function getVendor($userId);
    
    abstract protected function createVendor($itemArray);
    
    abstract protected function updateVendor(\Application\Entity\Item $item, $itemArray);
    
    abstract protected function deleteVendor(\Application\Entity\Item $item, $itemArray);*/
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

//    public function createItem($itemArray)
//    {
//        // Positive amount is Sale and negetive amount is Purchase
////        if ('vendor' == $itemArray['item_type']) {
////            $confirmationArray = $this->createVendor($itemArray);
////        } else {
//                $confirmationArray = $this->createItem($itemArray);
////        }
//
//        return $confirmationArray;
//    }
    
//    public function updateItem(\Application\Entity\Item $item, $itemArray)
//    {
//        // Positive amount is Sale and negetive amount is Purchase
////        if ('vendor' == $item->getItemType()) {
////            $confirmationArray = $this->updateVendor($item, $itemArray);
////        } else {
//            $confirmationArray = $this->updateItem($item, $itemArray);
////        }
//
//        return $confirmationArray;
//    }
    
//    public function deleteItem(\Application\Entity\Item $item, $itemArray)
//    {
////        if ('vendor' == $item->getItemType()) {
////            $confirmationArray = $this->deleteVendor($item, $itemArray);
////        } else {
//            $confirmationArray = $this->deleteItem($item, $itemArray);
////        }
//
//        return $confirmationArray;
//    }

}
