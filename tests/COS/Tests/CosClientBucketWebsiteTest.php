<?php

namespace COS\Tests;

use COS\Core\CosException;
use COS\Model\WebsiteConfig;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestCosClientBase.php';


class CosClientBucketWebsiteTest extends TestCosClientBase
{
    public function testBucket()
    {

        $websiteConfig = new WebsiteConfig("index.html", "error.html");

        try {
            $this->cosClient->putBucketWebsite($this->bucket, $websiteConfig);
        } catch (CosException $e) {
            var_dump($e->getMessage());
            $this->assertTrue(false);
        }

        try {
            sleep(2);
            $websiteConfig2 = $this->cosClient->getBucketWebsite($this->bucket);
            $this->assertEquals($websiteConfig->serializeToJson(), $websiteConfig2->serializeToJson());
        } catch (CosException $e) {
            $this->assertTrue(false);
        }
        try {
            $this->cosClient->deleteBucketWebsite($this->bucket);
        } catch (CosException $e) {
            $this->assertTrue(false);
        }
    }
}
