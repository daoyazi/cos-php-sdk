<?php

namespace COS\Tests;

use COS\Core\CosException;

class CosExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testCOS_exception()
    {
        try {
            throw new CosException("ERR");
            $this->assertTrue(false);
        } catch (CosException $e) {
            $this->assertNotNull($e);
            $this->assertEquals($e->getMessage(), "ERR");
        }
    }
}
