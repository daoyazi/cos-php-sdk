<?php

namespace COS\Tests;

use COS\Result\HeaderResult;
use COS\Http\ResponseCore;

/**
 * Class HeaderResultTest
 * @package COS\Tests
 */
class HeaderResultTest extends \PHPUnit_Framework_TestCase
{
    public function testGetHeader()
    {
        $response = new ResponseCore(array('key' => 'value'), "", 200);
        $result = new HeaderResult($response);
        $this->assertTrue($result->isOK());
        $this->assertTrue(is_array($result->getData()));
        $this->assertEquals($result->getData()['key'], 'value');
    }
}
