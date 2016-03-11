<?php
require_once __DIR__ . '/Common.php';

use COS\CosClient;
use COS\Core\CosException;

$bucket = Common::getBucketName();
$cosClient = Common::getCosClient();
if (is_null($cosClient)) exit(1);

createObjectDir($cosClient, $bucket);
listAllObjects($cosClient, $bucket);
listObjects($cosClient, $bucket);
putObject($cosClient, $bucket);
uploadFile($cosClient, $bucket);
getObject($cosClient, $bucket);
getObjectToLocalFile($cosClient, $bucket);
modifyMetaForObject($cosClient, $bucket);
getObjectMeta($cosClient, $bucket);
deleteObject($cosClient, $bucket);
deleteObjects($cosClient, $bucket);
doesObjectExist($cosClient, $bucket);

/**
 * 创建虚拟目录
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function createObjectDir($cosClient, $bucket)
{
    try {
        $cosClient->createObjectDir($bucket, "dir");
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}

/**
 * 把本地变量的内容到文件
 *
 * 简单上传,上传指定变量的内存值作为object的内容
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function putObject($cosClient, $bucket)
{
    $object = "cos-php-sdk-test/upload-test-object-name.txt";
    $content = file_get_contents(__FILE__);
    $options = array();
    try {
        $cosClient->putObject($bucket, $object, $content, $options);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}


/**
 * 上传指定的本地文件内容
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function uploadFile($cosClient, $bucket)
{
    $object = "cos-php-sdk-test/upload-test-object-name.txt";
    $filePath = __FILE__;
    $options = array();

    try {
        $cosClient->uploadFile($bucket, $object, $filePath, $options);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}

/**
 * 列出Bucket内所有目录和文件, 注意如果符合条件的文件数目超过设置的max-keys， 用户需要使用返回的nextMarker作为入参，通过
 * 循环调用ListObjects得到所有的文件，具体操作见下面的 listAllObjects 示例
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function listObjects($cosClient, $bucket)
{
    $prefix = '';
    $delimiter = '';
    $nextMarker = '';
    $maxkeys = 1000;
    $options = array(
        'delimiter' => $delimiter,
        'prefix' => $prefix,
        'max-keys' => $maxkeys,
        'marker' => $nextMarker,
    );
    try {
        $listObjectInfo = $cosClient->listObjects($bucket, $options);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    $objectList = $listObjectInfo->getObjectList(); // 文件列表
    $prefixList = $listObjectInfo->getPrefixList(); // 目录列表
    print(__FUNCTION__ . ": OK.  " . "There is <" . count($objectList) . "> objects.\n");

    // if (!empty($objectList)) {
    //     foreach ($objectList as $objectInfo) {
    //         print($objectInfo->getKey() . "\n");
    //     }
    // }
    // if (!empty($prefixList)) {
    //     foreach ($prefixList as $prefixInfo) {
    //         print($prefixInfo->getPrefix() . "\n");
    //     }
    // }
}

/**
 * 列出Bucket内所有目录和文件， 根据返回的nextMarker循环得到所有Objects
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function listAllObjects($cosClient, $bucket)
{
    //构造dir下的文件和虚拟目录
    printf("we will create 10 directories and 10 objects for test, please wait\n");
    for ($i = 0; $i < 10; $i += 1) {
        $cosClient->putObject($bucket, "dir/obj" . strval($i), "hi");
        $cosClient->createObjectDir($bucket, "dir/obj" . strval($i));
    }
    printf("directories and objects reated done\n");

    $prefix = 'dir/';
    $delimiter = '';
    $nextMarker = '';
    $maxkeys = 4;

    $cnt = 0;
    while (true) {
        $options = array(
            'delimiter' => $delimiter,
            'prefix' => $prefix,
            'max-keys' => $maxkeys,
            'marker' => $nextMarker,
        );

        try {
            $listObjectInfo = $cosClient->listObjects($bucket, $options);
        } catch (CosException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        // 得到nextMarker，从上一次listObjects读到的最后一个文件的下一个文件开始继续获取文件列表
        $nextMarker = $listObjectInfo->getNextMarker();
        $listObject = $listObjectInfo->getObjectList();
        $listPrefix = $listObjectInfo->getPrefixList();
        var_dump(count($listObject));
        var_dump(count($listPrefix));

        // if (!empty($listObject)) {
        //     foreach ($listObject as $objectInfo) {
        //       print($objectInfo->getKey() . "\n");
        //     }
        // }

        // if (!empty($listPrefix)) {
        //     foreach ($listPrefix as $prefixInfo) {
        //         print($prefixInfo->getPrefix() . "\n");
        //     }
        // }

        $cnt += count($listObject);

        if ($nextMarker === '') {
            break;
        }
    }
    printf(__FUNCTION__ . ": OK, and there are <" . $cnt . "> objects started with dir\n");
}

/**
 * 获取object的内容
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function getObject($cosClient, $bucket)
{
    $object = "cos-php-sdk-test/upload-test-object-name.txt";
    $options = array();
    try {
        $content = $cosClient->getObject($bucket, $object, $options);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
    if (file_get_contents(__FILE__) === $content) {
        print(__FUNCTION__ . ": FileContent checked OK" . "\n");
    } else {
        print(__FUNCTION__ . ": FileContent checked FAILED" . "\n");
    }
}

/**
 * get_object_to_local_file
 *
 * 获取object
 * 将object下载到指定的文件
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function getObjectToLocalFile($cosClient, $bucket)
{
    $object = "cos-php-sdk-test/upload-test-object-name.txt";
    $localfile = "upload-test-object-name.txt";
    $options = array(
        CosClient::COS_FILE_DOWNLOAD => $localfile,
    );

    try {
        $cosClient->getObject($bucket, $object, $options);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK, please check localfile: 'upload-test-object-name.txt'" . "\n");
    if (file_get_contents($localfile) === file_get_contents(__FILE__)) {
        print(__FUNCTION__ . ": FileContent checked OK" . "\n");
    } else {
        print(__FUNCTION__ . ": FileContent checked FAILED" . "\n");
    }
    if (file_exists($localfile)) {
        unlink($localfile);
    }
}

/**
 * 修改Object Meta
 * 利用copyObject接口的特性：当目的object和源object完全相同时，表示修改object的meta信息
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function modifyMetaForObject($cosClient, $bucket)
{
    $fromBucket = $bucket;
    $fromObject = "cos-php-sdk-test/upload-test-object-name.txt";
    $toBucket = $bucket;
    $toObject = $fromObject;
    $copyOptions = array(
        CosClient::COS_HEADERS => array(
            'Cache-Control' => 'max-age=60',
            'Content-Disposition' => 'attachment; filename="xxxxxx"',
        ),
    );
    try {
        $cosClient->copyObject($fromBucket, $fromObject, $toBucket, $toObject, $copyOptions);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}

/**
 * 获取object meta, 也就是getObjectMeta接口
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function getObjectMeta($cosClient, $bucket)
{
    $object = "cos-php-sdk-test/upload-test-object-name.txt";
    try {
        $objectMeta = $cosClient->getObjectMeta($bucket, $object);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");

    //COS dose not support Content-Disposition for now
/*    if (isset($objectMeta[strtolower('Content-Disposition')]) &&
        'attachment; filename="xxxxxx"' === $objectMeta[strtolower('Content-Disposition')]
    ) {
        print(__FUNCTION__ . ": ObjectMeta checked OK" . "\n");
    } else {
        print(__FUNCTION__ . ": ObjectMeta checked FAILED" . "\n");
    }
*/
}

/**
 * 删除object
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function deleteObject($cosClient, $bucket)
{
    $object = "cos-php-sdk-test/upload-test-object-name.txt";
    try {
        $cosClient->deleteObject($bucket, $object);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}


/**
 * 批量删除object
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function deleteObjects($cosClient, $bucket)
{
    $objects = array();
    $objects[] = "cos-php-sdk-test/upload-test-object-name.txt";
    $objects[] = "cos-php-sdk-test/upload-test-object-name.txt.copy";
    try {
        $cosClient->deleteObjects($bucket, $objects);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}

/**
 * 判断object是否存在
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function doesObjectExist($cosClient, $bucket)
{
    $object = "cos-php-sdk-test/upload-test-object-name.txt";
    try {
        $exist = $cosClient->doesObjectExist($bucket, $object);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": OK" . "\n");
}

