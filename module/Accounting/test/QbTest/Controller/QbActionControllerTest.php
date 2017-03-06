<?php

namespace QbTest\Controller;

use \Bootstrap;
use PHPUnit_Framework_TestCase;

class QbActionControllerTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $serviceManager = Bootstrap::getServiceManager();
    }
    
    
    public function testOk() {
	$this->assertEquals(true,true);
    }
    
}
