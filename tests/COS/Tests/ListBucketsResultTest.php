<?php

namespace COS\Tests;

use COS\Core\CosException;
use COS\Http\ResponseCore;
use COS\Result\ListBucketsResult;

class ListBucketsResultTest extends \PHPUnit_Framework_TestCase
{
    private $validXml = <<<BBBB
{
    "Owner":{"ID": "ut_test_put_bucket","DisplayName": "ut_test_put_bucket"},
    "Buckets":[
            {
                "Name": "xz02tphky6fjfiuc0",
                "CreationDate":"2016-02-25T13:11:11+0800",
                "Location": "cos-cn-hangzhou-a"
            },
            {
                "Name": "xz02tphky6fjfiuc1",
                "CreationDate":"2016-02-25T13:11:24+0800",
                "Location": "cos-cn-hangzhou-a"
            }
    ]
}
BBBB;

    private $nullXml = <<<BBBB
{
    "Owner":{"ID": "ut_test_put_bucket","DisplayName": "ut_test_put_bucket"},
    "Buckets":[]
}
BBBB;

    public function testParseValidXml()
    {
        $response = new ResponseCore(array(), $this->validXml, 200);
        $result = new ListBucketsResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $bucketListInfo = $result->getData();
        $this->assertEquals(2, count($bucketListInfo->getBucketList()));
    }

    public function testParseNullXml()
    {
        $response = new ResponseCore(array(), $this->nullXml, 200);
        $result = new ListBucketsResult($response);
        $this->assertTrue($result->isOK());
        $this->assertNotNull($result->getData());
        $this->assertNotNull($result->getRawResponse());
        $bucketListInfo = $result->getData();
        $this->assertEquals(0, count($bucketListInfo->getBucketList()));
    }

    public function test403()
    {
        $errorHeader = array(
            'x-cos-request-id' => '1a2b-3c4d'
        );

        $errorBody = <<< BBBB
{
    "Code": "NoSuchBucket",
    "Message": "The specified bucket does not exist",
    "RequestId": "1a2b-3c4d",
    "HostId": "hello.cos-test.chianc.com",
    "BucketName": "hello"
}
BBBB;
        $response = new ResponseCore($errorHeader, $errorBody, 403);
        try {
            new ListBucketsResult($response);
        } catch (CosException $e) {
            $this->assertEquals(
                $e->getErrorMessage(),
                'NoSuchBucket');
            $this->assertEquals($e->getHTTPStatus(), '403');
            $this->assertEquals($e->getRequestId(), '1a2b-3c4d');
            $this->assertEquals($e->getErrorCode(), 'NoSuchBucket');
            $this->assertEquals($e->getErrorMessage(), 'NoSuchBucket');
            $this->assertEquals($e->getDetails(), $errorBody);
        }
    }
}
