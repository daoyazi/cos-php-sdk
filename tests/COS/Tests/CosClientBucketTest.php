<?php

namespace COS\Tests;

use COS\Core\CosException;
use COS\Core\CosUtil;
use COS\CosClient;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestCosClientBase.php';


class CosClientBucketTest extends TestCosClientBase
{
    public function testBucketWithInvalidName()
    {
        try {
            $this->cosClient->createBucket("SSSS");
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals('"SSSS"bucket name is invalid', $e->getMessage());
        }
    }

    public function testBucketWithInvalidACL()
    {
        try {
            $this->cosClient->createBucket($this->bucket, "invalid");
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals('invalid:acl is invalid(private,public-read)', $e->getMessage());
        }
    }

    public function testBucket()
    {
//        $this->cosClient->createBucket($this->bucket, CosClient::COS_ACL_TYPE_PUBLIC_READ);

        $bucketListInfo = $this->cosClient->listBuckets();
        $this->assertNotNull($bucketListInfo);
        $bucketList = $bucketListInfo->getBucketList();
        $this->assertTrue(is_array($bucketList));
        $this->assertGreaterThan(0, count($bucketList));
        $this->cosClient->putBucketAcl($this->bucket, CosClient::COS_ACL_TYPE_PUBLIC_READ);
        $this->assertEquals($this->cosClient->getBucketAcl($this->bucket), CosClient::COS_ACL_TYPE_PUBLIC_READ);

        $this->assertTrue($this->cosClient->doesBucketExist($this->bucket));
        $this->assertFalse($this->cosClient->doesBucketExist($this->bucket . '-notexist'));

        try {
            $this->cosClient->deleteBucket($this->bucket);
            //we need create the bucket again for later test
            $this->cosClient->createBucket($this->bucket, CosClient::COS_ACL_TYPE_PUBLIC_READ);
        } catch (CosException $e) {
            $this->assertEquals("BucketNotEmpty", $e->getErrorCode());
            $this->assertEquals("409", $e->getHTTPStatus());
        }


    }
}
