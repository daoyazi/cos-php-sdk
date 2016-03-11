<?php
require_once __DIR__ . '/Common.php';

use COS\CosClient;
use COS\Core\CosException;

$cosClient = Common::getCosClient();
if (is_null($cosClient)) exit(1);
$bucket = Common::getBucketName();

createBucket($cosClient, $bucket);
doesBucketExist($cosClient, $bucket);
putBucketAcl($cosClient, $bucket);
getBucketAcl($cosClient, $bucket);
listBuckets($cosClient);
//不删除该Bucket, 后面的单元测试可以基于该bucket进行
//deleteBucket($cosClient, $bucket);

/**
 * 创建一个存储空间
 * acl 指的是bucket的访问控制权限，有两种，私有读写，公共读私有写。
 * 私有读写就是只有bucket的拥有者或授权用户才有权限操作
 * 两种权限分别对应 (CosClient::COS_ACL_TYPE_PRIVATE，CosClient::COS_ACL_TYPE_PUBLIC_READ)
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 要创建的存储空间名称
 * @return null
 */
function createBucket($cosClient, $bucket)
{
    try {
        $cosClient->createBucket($bucket, CosClient::COS_ACL_TYPE_PUBLIC_READ);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED.    ");
        printf($e->getErrorMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}

/**
 *  判断Bucket是否存在
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 */
function doesBucketExist($cosClient, $bucket)
{
    try {
        $res = $cosClient->doesBucketExist($bucket);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    if ($res === true) {
        print(__FUNCTION__ . ": OK" . "\n");
    } else {
        print(__FUNCTION__ . ": FAILED" . "\n");
    }
}

/**
 * 删除bucket，如果bucket不为空则bucket无法删除成功， 不为空表示bucket既没有object，也没有未完成的multipart上传时的parts
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 待删除的存储空间名称
 * @return null
 */
function deleteBucket($cosClient, $bucket)
{
    try {
        $cosClient->deleteBucket($bucket);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}

/**
 * 设置bucket的acl配置
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function putBucketAcl($cosClient, $bucket)
{
    $acl = CosClient::COS_ACL_TYPE_PRIVATE;
    try {
        $cosClient->putBucketAcl($bucket, $acl);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}


/**
 * 获取bucket的acl配置
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function getBucketAcl($cosClient, $bucket)
{
    try {
        $res = $cosClient->getBucketAcl($bucket);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}


/**
 * 列出用户所有的Bucket
 *
 * @param CosClient $cosClient CosClient实例
 * @return null
 */
function listBuckets($cosClient)
{
    $bucketList = null;
    try {
        $bucketListInfo = $cosClient->listBuckets();
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
    $bucketList = $bucketListInfo->getBucketList();
    foreach ($bucketList as $bucket) {
        print($bucket->getLocation() . "\t" . $bucket->getName() . "\t" . $bucket->getCreatedate() . "\n");
    }
}
