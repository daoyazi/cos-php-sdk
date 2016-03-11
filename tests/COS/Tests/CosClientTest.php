<?php

namespace COS\Tests;

use COS\Core\CosException;
use COS\CosClient;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Config.php';


class CosClientTest extends \PHPUnit_Framework_TestCase
{
    public function testConstrunct()
    {
        try {
            $cosClient = new CosClient(Config::COS_ACCESS_ID, Config::COS_ACCESS_KEY, Config::COS_ENDPOINT);
            $this->assertFalse($cosClient->isUseSSL());
            $cosClient->setUseSSL(true);
            $this->assertTrue($cosClient->isUseSSL());
            $this->assertTrue(true);
            $this->assertEquals(3, $cosClient->getMaxRetries());
            $cosClient->setMaxTries(4);
            $this->assertEquals(4, $cosClient->getMaxRetries());
            $cosClient->setTimeout(10);
            $cosClient->setConnectTimeout(20);
        } catch (CosException $e) {
            assertFalse(true);
        }
    }

    public function testConstrunct2()
    {
        try {
            $cosClient = new CosClient(Config::COS_ACCESS_ID, "", Config::COS_ENDPOINT);
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals("access key secret is empty", $e->getMessage());
        }
    }

    public function testConstrunct3()
    {
        try {
            $cosClient = new CosClient("", Config::COS_ACCESS_KEY, Config::COS_ENDPOINT);
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals("access key id is empty", $e->getMessage());
        }
    }

    public function testConstrunct4()
    {
        try {
            $cosClient = new CosClient(Config::COS_ACCESS_ID, Config::COS_ACCESS_KEY, "");
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals('endpoint is empty', $e->getMessage());
        }
    }

    public function testConstrunct5()
    {
        try {
            $cosClient = new CosClient(Config::COS_ACCESS_ID, Config::COS_ACCESS_KEY, "123.123.123.1");
        } catch (CosException $e) {
            $this->assertTrue(false);
        }
    }

    public function testConstrunct6()
    {
        try {
            $cosClient = new CosClient(Config::COS_ACCESS_ID, Config::COS_ACCESS_KEY, "https://123.123.123.1");
            $this->assertTrue($cosClient->isUseSSL());
        } catch (CosException $e) {
            $this->assertTrue(false);
        }
    }

    public function testConstrunct7()
    {
        try {
            $cosClient = new CosClient(Config::COS_ACCESS_ID, Config::COS_ACCESS_KEY, "http://123.123.123.1");
            $this->assertFalse($cosClient->isUseSSL());
        } catch (CosException $e) {
            $this->assertTrue(false);
        }
    }
}
