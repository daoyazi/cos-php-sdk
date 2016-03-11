<?php
require_once __DIR__ . '/Common.php';

use COS\CosClient;
use COS\Core\CosException;
use COS\Model\WebsiteConfig;

$bucket = Common::getBucketName();
$cosClient = Common::getCosClient();
if (is_null($cosClient)) exit(1);

//*******************************简单使用***************************************************************

// 设置Bucket的静态网站托管模式
$websiteConfig = new WebsiteConfig("index.html", "error.html");
$cosClient->putBucketWebsite($bucket, $websiteConfig);
Common::println("bucket $bucket websiteConfig created:" . $websiteConfig->serializeToJson());

// 查看Bucket的静态网站托管状态
$websiteConfig = $cosClient->getBucketWebsite($bucket);
Common::println("bucket $bucket websiteConfig fetched:" . $websiteConfig->serializeToJson());

// 删除Bucket的静态网站托管模式
$cosClient->deleteBucketWebsite($bucket);
Common::println("bucket $bucket websiteConfig deleted");

//******************************* 完整用法参考下面函数 ****************************************************

putBucketWebsite($cosClient, $bucket);
getBucketWebsite($cosClient, $bucket);
deleteBucketWebsite($cosClient, $bucket);
getBucketWebsite($cosClient, $bucket);

/**
 * 设置bucket的静态网站托管模式配置
 *
 * @param $cosClient CosClient
 * @param  $bucket string 存储空间名称
 * @return null
 */
function putBucketWebsite($cosClient, $bucket)
{
    $websiteConfig = new WebsiteConfig("index.html", "error.html");
    try {
        $cosClient->putBucketWebsite($bucket, $websiteConfig);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}

/**
 * 获取bucket的静态网站托管状态
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function getBucketWebsite($cosClient, $bucket)
{
    $websiteConfig = null;
    try {
        $websiteConfig = $cosClient->getBucketWebsite($bucket);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
    print($websiteConfig->serializeToJson() . "\n");
}

/**
 * 删除bucket的静态网站托管模式配置
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function deleteBucketWebsite($cosClient, $bucket)
{
    try {
        $cosClient->deleteBucketWebsite($bucket);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}
