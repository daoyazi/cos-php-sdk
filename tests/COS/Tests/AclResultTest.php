<?php

namespace COS\Tests;

use COS\Result\AclResult;
use COS\Core\CosException;
use COS\Http\ResponseCore;

class AclResultTest extends \PHPUnit_Framework_TestCase
{

    private $validJson = <<<BBBB
{
    "Owner":{
        "ID": "00220120222",
        "DisplayName": "user_example"
    },
    "AccessControlList":{"Grant": "public-read"}
}
BBBB;

    private $invalidJson = <<<BBBB
{
    "AccessControlPolicy":{}
}
BBBB;

    public function testParseValidJson()
    {
        $response = new ResponseCore(array(), $this->validJson, 200);
        $result = new AclResult($response);
        $this->assertEquals("public-read", $result->getData());
    }

    public function testParseNullJson()
    {
        $response = new ResponseCore(array(), "", 200);
        try {
            new AclResult($response);
            $this->assertTrue(false);
        } catch (CosException $e) {
            $this->assertEquals('body is null', $e->getMessage());
        }
    }

    public function testParseInvalidJson()
    {
        $response = new ResponseCore(array(), $this->invalidJson, 200);
        try {
            new AclResult($response);
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals("json format exception", $e->getMessage());
        }
    }
}
