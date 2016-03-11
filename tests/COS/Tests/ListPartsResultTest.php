<?php

namespace COS\Tests;

use COS\Result\ListPartsResult;
use COS\Http\ResponseCore;

/**
 * Class ListPartsResultTest
 * @package COS\Tests
 */
class ListPartsResultTest extends \PHPUnit_Framework_TestCase
{
    private $validXml = <<<BBBB
{
    "Bucket": "multipart_upload",
    "Key": "multipart.data",
    "UploadId": "0004B999EF5A239BB9138C6227D69F95",
    "PartNumberMarker": "0",
    "NextPartNumberMarker": "5",
    "MaxParts": "1000",
    "IsTruncated": "false",
    "Parts":[
    {
        "PartNumber": 1,
        "LastModified": "2012-02-23T07:01:34.000Z",
        "ETag": "3349DC700140D7F86A078484278075A9",
        "Size": 6291456
    },
    {
        "PartNumber": 2,
        "LastModified": "2012-02-23T07:01:12.000Z",
        "ETag": "3349DC700140D7F86A078484278075A9",
        "Size": 6291456
    },
    {
        "PartNumber": 5,
        "LastModified": "2012-02-23T07:02:03.000Z",
        "ETag": "7265F4D211B56873A381D321F586E4A9",
        "Size": 1024
    }
    ]
}

BBBB;

    public function testParseValidXml()
    {
        $response = new ResponseCore(array(), $this->validXml, 200);
        $result = new ListPartsResult($response);
        $listPartsInfo = $result->getData();
        $this->assertEquals("multipart_upload", $listPartsInfo->getBucket());
        $this->assertEquals("multipart.data", $listPartsInfo->getKey());
        $this->assertEquals("0004B999EF5A239BB9138C6227D69F95", $listPartsInfo->getUploadId());
        $this->assertEquals(5, $listPartsInfo->getNextPartNumberMarker());
        $this->assertEquals(1000, $listPartsInfo->getMaxParts());
        $this->assertEquals("false", $listPartsInfo->getIsTruncated());
        $this->assertEquals(3, count($listPartsInfo->getListPart()));
        $this->assertEquals(1, $listPartsInfo->getListPart()[0]->getPartNumber());
        $this->assertEquals('2012-02-23T07:01:34.000Z', $listPartsInfo->getListPart()[0]->getLastModified());
        $this->assertEquals('3349DC700140D7F86A078484278075A9', $listPartsInfo->getListPart()[0]->getETag());
        $this->assertEquals(6291456, $listPartsInfo->getListPart()[0]->getSize());
    }
}
