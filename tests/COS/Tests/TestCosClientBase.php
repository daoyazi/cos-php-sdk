<?php

namespace COS\Tests;

use COS\CosClient;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Common.php';

class TestCosClientBase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CosClient
     */
    protected $cosClient;

    /**
     * @var string
     */
    protected $bucket;

    public function setUp()
    {
        $this->bucket = Common::getBucketName();
        $this->cosClient = Common::getCosClient();
/*        try {
            $this->cosClient->createBucket($this->bucket);
        } catch (CosException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
*/    }

    public function tearDown()
    {

    }

}
