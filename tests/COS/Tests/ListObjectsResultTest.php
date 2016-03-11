<?php

namespace COS\Tests;


use COS\Result\ListObjectsResult;
use COS\Http\ResponseCore;

class ListObjectsResultTest extends \PHPUnit_Framework_TestCase
{

    private $validXml1 = <<<BBBB
{
    "Name": "testbucket-hf",
    "Prefix": "",
    "Marker": "",
    "Delimiter": "/",
    "MaxKeys": "1000",
    "IsTruncated": "false",
    "CommonPrefixes":[
    { "Prefix": "cos-php-sdk-test/"},
    { "Prefix": "test/"}
    ]
}
BBBB;

    private $validXml2 = <<<BBBB
{
    "Name": "testbucket-hf",
    "Prefix": "cos-php-sdk-test/",
    "Marker": "xx",
    "Delimiter": "/",
    "MaxKeys": "1000",
    "IsTruncated": "false",
    "Contents":[
    {
        "Key": "cos-php-sdk-test/upload-test-object-name.txt",
        "LastModified": "2015-11-18T03:36:00.000Z",
        "ETag": "89B9E567E7EB8815F2F7D41851F9A2CD",
        "Size": "13115",
        "StorageClass": "Standard",
        "Owner":{"ID": "usera", "DisplayName": "userA"}
    }
    ]
}
BBBB;

    private $validXmlWithEncodedKey = <<<BBBB
{
    "Name": "testbucket-hf",
    "EncodingType": "url",
    "Prefix": "php%2Fprefix",
    "Marker": "php%2Fmarker",
    "NextMarker": "php%2Fnext-marker",
    "Delimiter": "%2F",
    "MaxKeys": "1000",
    "IsTruncated": "true",
    "Contents":[
    {
        "Key": "php/a%2Bb",
        "LastModified": "2015-11-18T03:36:00.000Z",
        "ETag": "89B9E567E7EB8815F2F7D41851F9A2CD",
        "Type": "Normal",
        "Size": "13115",
        "StorageClass": "Standard",
        "Owner":{"ID": "userb", "DisplayName": "userB"}
    }
    ]
}
BBBB;

    public function testParseValidXml1()
    {
        $response = new ResponseCore(array(), $this->validXml1, 200);
        $result = new ListObjectsResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $objectListInfo = $result->getData();
        $this->assertEquals(2, count($objectListInfo->getPrefixList()));
        $this->assertEquals(0, count($objectListInfo->getObjectList()));
        $this->assertEquals('testbucket-hf', $objectListInfo->getBucketName());
        $this->assertEquals('', $objectListInfo->getPrefix());
        $this->assertEquals('', $objectListInfo->getMarker());
        $this->assertEquals(1000, $objectListInfo->getMaxKeys());
        $this->assertEquals('/', $objectListInfo->getDelimiter());
        $this->assertEquals('false', $objectListInfo->getIsTruncated());
        $this->assertEquals('cos-php-sdk-test/', $objectListInfo->getPrefixList()[0]->getPrefix());
        $this->assertEquals('test/', $objectListInfo->getPrefixList()[1]->getPrefix());
    }

    public function testParseValidXml2()
    {
        $response = new ResponseCore(array(), $this->validXml2, 200);
        $result = new ListObjectsResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $objectListInfo = $result->getData();
        $this->assertEquals(0, count($objectListInfo->getPrefixList()));
        $this->assertEquals(1, count($objectListInfo->getObjectList()));
        $this->assertEquals('testbucket-hf', $objectListInfo->getBucketName());
        $this->assertEquals('cos-php-sdk-test/', $objectListInfo->getPrefix());
        $this->assertEquals('xx', $objectListInfo->getMarker());
        $this->assertEquals(1000, $objectListInfo->getMaxKeys());
        $this->assertEquals('/', $objectListInfo->getDelimiter());
        $this->assertEquals('false', $objectListInfo->getIsTruncated());
        $this->assertEquals('cos-php-sdk-test/upload-test-object-name.txt', $objectListInfo->getObjectList()[0]->getKey());
        $this->assertEquals('2015-11-18T03:36:00.000Z', $objectListInfo->getObjectList()[0]->getLastModified());
        $this->assertEquals('89B9E567E7EB8815F2F7D41851F9A2CD', $objectListInfo->getObjectList()[0]->getETag());

//        $this->assertEquals('Normal', $objectListInfo->getObjectList()[0]->getType());
        $this->assertEquals(13115, $objectListInfo->getObjectList()[0]->getSize());
        $this->assertEquals('Standard', $objectListInfo->getObjectList()[0]->getStorageClass());
    }

    public function testParseValidXmlWithEncodedKey()
    {
        $response = new ResponseCore(array(), $this->validXmlWithEncodedKey, 200);
        $result = new ListObjectsResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $objectListInfo = $result->getData();
        $this->assertEquals(0, count($objectListInfo->getPrefixList()));
        $this->assertEquals(1, count($objectListInfo->getObjectList()));
        $this->assertEquals('testbucket-hf', $objectListInfo->getBucketName());
        $this->assertEquals('php/prefix', $objectListInfo->getPrefix());
        $this->assertEquals('php/marker', $objectListInfo->getMarker());
        $this->assertEquals('php/next-marker', $objectListInfo->getNextMarker());
        $this->assertEquals(1000, $objectListInfo->getMaxKeys());
        $this->assertEquals('/', $objectListInfo->getDelimiter());
        $this->assertEquals('true', $objectListInfo->getIsTruncated());
        $this->assertEquals('php/a+b', $objectListInfo->getObjectList()[0]->getKey());
        $this->assertEquals('2015-11-18T03:36:00.000Z', $objectListInfo->getObjectList()[0]->getLastModified());
        $this->assertEquals('89B9E567E7EB8815F2F7D41851F9A2CD', $objectListInfo->getObjectList()[0]->getETag());
        $this->assertEquals('Normal', $objectListInfo->getObjectList()[0]->getType());
        $this->assertEquals(13115, $objectListInfo->getObjectList()[0]->getSize());
        $this->assertEquals('Standard', $objectListInfo->getObjectList()[0]->getStorageClass());
    }
}
