<?php

namespace COS\Tests;


use COS\Result\GetWebsiteResult;
use COS\Http\ResponseCore;
use COS\Core\CosException;

class GetWebsiteResultTest extends \PHPUnit_Framework_TestCase
{
    private $validJson = <<<BBBB
{
    "IndexDocument":{
        "Suffix": "index.html"
    },
    "ErrorDocument":{
        "Key": "errorDocument.html"
    }
}
BBBB;

    public function testParseValidJson()
    {
        $response = new ResponseCore(array(), $this->validJson, 200);
        $result = new GetWebsiteResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $websiteConfig = $result->getData();
        $this->assertEquals($this->cleanJson($this->validJson), $this->cleanJson($websiteConfig->serializeToJson()));
    }

    private function cleanJson($json)
    {
        return str_replace("\n", "", str_replace("\r", "", str_replace(" ", "", $json)));
    }

    public function testInvalidResponse()
    {
        $response = new ResponseCore(array(), $this->validJson, 300);
        try {
            new GetWebsiteResult($response);
            $this->assertTrue(false);
        } catch (CosException $e) {

        }
    }
}
