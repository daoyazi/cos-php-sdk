<?php

namespace COS\Tests;


use COS\Core\CosException;
use COS\Result\InitiateMultipartUploadResult;
use COS\Http\ResponseCore;

class InitiateMultipartUploadResultTest extends \PHPUnit_Framework_TestCase
{
    private $validXml = <<<BBBB
{
    "Bucket": "multipart_upload",
    "Key": "multipart.data",
    "UploadId": "0004B9894A22E5B1888A1E29F8236E2D"
}
BBBB;

    private $invalidXml = <<<BBBB
{
    "Bucket": "multipart_upload",
    "Key": "multipart.data"
}
BBBB;


    public function testParseValidXml()
    {
        $response = new ResponseCore(array(), $this->validXml, 200);
        $result = new InitiateMultipartUploadResult($response);
        $this->assertEquals("0004B9894A22E5B1888A1E29F8236E2D", $result->getData());
    }

    public function testParseInvalidXml()
    {
        $response = new ResponseCore(array(), $this->invalidXml, 200);
        try {
            $result = new InitiateMultipartUploadResult($response);
            $this->assertTrue(false);
        } catch (CosException $e) {

        }
    }
}
