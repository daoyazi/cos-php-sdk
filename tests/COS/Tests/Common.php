<?php

namespace COS\Tests;

require_once __DIR__ . '/../../../autoload.php';
require_once __DIR__ . '/Config.php';

use COS\CosClient;
use COS\Core\CosException;

/**
 * Class Common
 *
 * 示例程序【Samples/*.php】 的Common类，用于获取CosClient实例和其他公用方法
 */
class Common
{
    const endpoint = Config::COS_ENDPOINT;
    const accessKeyId = Config::COS_ACCESS_ID;
    const accessKeySecret = Config::COS_ACCESS_KEY;
    const bucket = Config::COS_TEST_BUCKET;

    /**
     * 根据Config配置，得到一个CosClient实例
     *
     * @return CosClient 一个CosClient实例
     */
    public static function getCosClient()
    {
        try {
            $cosClient = new CosClient(self::accessKeyId, self::accessKeySecret, self::endpoint);
        } catch (CosException $e) {
            printf(__FUNCTION__ . "creating CosClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
            return null;
        }
        return $cosClient;
    }

    public static function getBucketName()
    {
        return self::bucket;
    }

    /**
     * 工具方法，创建一个bucket
     */
    public static function createBucket()
    {
        $cosClient = self::getCosClient();
        if (is_null($cosClient)) exit(1);
        $bucket = self::getBucketName();
        $acl = CosClient::COS_ACL_TYPE_PUBLIC_READ;
        try {
            $cosClient->createBucket($bucket, $acl);
        } catch (CosException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        print(__FUNCTION__ . ": OK" . "\n");
    }
}
