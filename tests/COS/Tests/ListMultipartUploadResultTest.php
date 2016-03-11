<?php

namespace COS\Tests;

use COS\Result\ListMultipartUploadResult;
use COS\Http\ResponseCore;

/**
 * Class ListMultipartUploadResultTest
 * @package COS\Tests
 */
class ListMultipartUploadResultTest extends \PHPUnit_Framework_TestCase
{
    private $validXml = <<<BBBB
{
    "Bucket": "cos-example",
    "NextUploadIdMarker": "0004B99B8E707874FC2D692FA5D77D3F",
    "IsTruncated": "false",
    "MaxUploads": "1000",
    "NextKeyMarker": "cos.avi",
    "Uploads":[
    {
        "Key": "multipart.data",
        "UploadId": "0004B999EF518A1FE585B0C9360DC4C8",
        "Initiated": "2012-02-23T04:18:23.000Z"
    },
    {
        "Key": "multipart.data",
        "UploadId": "0004B999EF5A239BB9138C6227D69F95",
        "Initiated": "2012-02-23T04:18:23.000Z"
    },
    {
        "Key": "cos.avi",
        "UploadId": "0004B99B8E707874FC2D692FA5D77D3F",
        "Initiated": "2012-02-23T06:14:27.000Z"
    }
    ]
}
BBBB;

    private $validXmlWithEncodedKey = <<<BBBB
{
    "Bucket": "cos-example",
    "NextUploadIdMarker": "0004B99B8E707874FC2D692FA5D77D3F",
    "IsTruncated": "true",
    "MaxUploads": "1000",
    "NextKeyMarker": "php%2Bnext-key-marker",
    "Uploads":[
    {
        "Key": "php%2Bkey-1",
        "UploadId": "0004B999EF518A1FE585B0C9360DC4C8",
        "Initiated": "2012-02-23T04:18:23.000Z"
    },
    {
        "Key": "php%2Bkey-2",
        "UploadId": "0004B999EF5A239BB9138C6227D69F95",
        "Initiated": "2012-02-23T04:18:23.000Z"
    },
    {
        "Key": "php%2Bkey-3",
        "UploadId": "0004B99B8E707874FC2D692FA5D77D3F",
        "Initiated": "2012-02-23T06:14:27.000Z"
    }
    ]
}
BBBB;

    public function testParseValidXml()
    {
        $response = new ResponseCore(array(), $this->validXml, 200);
        $result = new ListMultipartUploadResult($response);
        $listMultipartUploadInfo = $result->getData();
        $this->assertEquals("cos-example", $listMultipartUploadInfo->getBucket());
        $this->assertEquals("cos.avi", $listMultipartUploadInfo->getNextKeyMarker());
        $this->assertEquals("0004B99B8E707874FC2D692FA5D77D3F", $listMultipartUploadInfo->getNextUploadIdMarker());
        $this->assertEquals("1000", $listMultipartUploadInfo->getMaxUploads());
        $this->assertEquals("false", $listMultipartUploadInfo->getIsTruncated());
        $this->assertEquals("multipart.data", $listMultipartUploadInfo->getUploads()[0]->getKey());
        $this->assertEquals("0004B999EF518A1FE585B0C9360DC4C8", $listMultipartUploadInfo->getUploads()[0]->getUploadId());
        $this->assertEquals("2012-02-23T04:18:23.000Z", $listMultipartUploadInfo->getUploads()[0]->getInitiated());
    }

    public function testParseValidXmlWithEncodedKey()
    {
        $response = new ResponseCore(array(), $this->validXmlWithEncodedKey, 200);
        $result = new ListMultipartUploadResult($response);
        $listMultipartUploadInfo = $result->getData();
        $this->assertEquals("cos-example", $listMultipartUploadInfo->getBucket());
//        $this->assertEquals("php+next-key-marker", $listMultipartUploadInfo->getNextKeyMarker());
//        $this->assertEquals(3, $listMultipartUploadInfo->getUploadIdMarker());
        $this->assertEquals("0004B99B8E707874FC2D692FA5D77D3F", $listMultipartUploadInfo->getNextUploadIdMarker());
//       $this->assertEquals("/", $listMultipartUploadInfo->getDelimiter());
//        $this->assertEquals("php+prefix", $listMultipartUploadInfo->getPrefix());
        $this->assertEquals(1000, $listMultipartUploadInfo->getMaxUploads());
        $this->assertEquals("true", $listMultipartUploadInfo->getIsTruncated());
//        $this->assertEquals("php+key-1", $listMultipartUploadInfo->getUploads()[0]->getKey());
        $this->assertEquals("0004B999EF518A1FE585B0C9360DC4C8", $listMultipartUploadInfo->getUploads()[0]->getUploadId());
        $this->assertEquals("2012-02-23T04:18:23.000Z", $listMultipartUploadInfo->getUploads()[0]->getInitiated());
    }
}
