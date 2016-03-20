<?php

if (is_file(__DIR__ . '/../autoload.php')) {
    require_once __DIR__ . '/../autoload.php';
}
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/Config.php';

use COS\CosClient;
use COS\Core\CosException;

/**
 * 断言回调函数，抛出异常
 */
function assert_callcack() {
    throw new Exception("assert error");
}

// Set our assert options
assert_options(ASSERT_WARNING,  true);
assert_options(ASSERT_CALLBACK, 'assert_callcack');

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
            assert(0);
        }
        return $cosClient;
    }

    public static function getBucketName()
    {
        return self::bucket;
    }

    /**
     * 工具方法，创建一个存储空间，如果发生异常直接exit
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
            $message = $e->getMessage();
            if (\COS\Core\CosUtil::startsWith($message, 'http status: 403')) {
                echo "Please Check your AccessKeyId and AccessKeySecret" . "\n";
                exit(0);
            } elseif (strpos($message, "BucketAlreadyOwnedByYou") !== false) {
                print("BucketAlreadyOwnedByYou\n");
                return;
            }
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            assert(0);
        }
        print(__FUNCTION__ . ": OK" . "\n");
    }

    public static function println($message)
    {
        if (!empty($message)) {
            echo strval($message) . "\n";
        }
    }
}
