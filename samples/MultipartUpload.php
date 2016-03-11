<?php
require_once __DIR__ . '/Common.php';

use COS\CosClient;
use COS\Core\CosUtil;
use COS\Core\CosException;

$bucket = Common::getBucketName();
$cosClient = Common::getCosClient();
if (is_null($cosClient)) exit(1);

//*******************************简单使用***************************************************************

/**
 * 查看完整用法中的 "putObjectByRawApis"函数，查看使用基础的分片上传api进行文件上传，用户可以基于这个自行实现断点续传等功能
 */

// 使用分片上传接口上传文件, 接口会根据文件大小决定是使用普通上传还是分片上传
$cosClient->multiuploadFile($bucket, "file.php", __FILE__, array());
Common::println("local file " . __FILE__ . " is uploaded to the bucket $bucket, file.php");


// 上传本地目录到bucket内的targetdir子目录中
$cosClient->uploadDir($bucket, "targetdir", __DIR__);
Common::println("local dir " . __DIR__ . " is uploaded to the bucket $bucket, targetdir/");


// 列出当前未完成的分片上传
$listMultipartUploadInfo = $cosClient->listMultipartUploads($bucket, array());


//******************************* 完整用法参考下面函数 ****************************************************

multiuploadFile($cosClient, $bucket);
putObjectByRawApis($cosClient, $bucket);
uploadDir($cosClient, $bucket);
listMultipartUploads($cosClient, $bucket);

/**
 * 通过multipart上传文件
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 */
function multiuploadFile($cosClient, $bucket)
{
    $object = "test/multipart-test.txt";
    $file = __FILE__;
    $options = array();

    try {
        $cosClient->multiuploadFile($bucket, $object, $file, $options);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ":  OK" . "\n");
}

/**
 * 使用基本的api分阶段进行分片上传
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @throws CosException
 */
function putObjectByRawApis($cosClient, $bucket)
{
    $object = "test/multipart-test.txt";
    /**
     *  step 1. 初始化一个分块上传事件, 也就是初始化上传Multipart, 获取upload id
     */
    try {
        $uploadId = $cosClient->initiateMultipartUpload($bucket, $object);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": initiateMultipartUpload FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    print(__FUNCTION__ . ": initiateMultipartUpload OK" . "\n");
    /*
     * step 2. 上传分片
     */
    $partSize = 10 * 1024 * 1024;
    $uploadFile = __FILE__;
    $uploadFileSize = filesize($uploadFile);
    $pieces = $cosClient->generateMultiuploadParts($uploadFileSize, $partSize);
    $responseUploadPart = array();
    $uploadPosition = 0;
    $isCheckMd5 = true;
    foreach ($pieces as $i => $piece) {
        $fromPos = $uploadPosition + (integer)$piece[$cosClient::COS_SEEK_TO];
        $toPos = (integer)$piece[$cosClient::COS_LENGTH] + $fromPos - 1;
        $upOptions = array(
            $cosClient::COS_FILE_UPLOAD => $uploadFile,
            $cosClient::COS_PART_NUM => ($i + 1),
            $cosClient::COS_SEEK_TO => $fromPos,
            $cosClient::COS_LENGTH => $toPos - $fromPos + 1,
            $cosClient::COS_CHECK_MD5 => $isCheckMd5,
        );
        if ($isCheckMd5) {
            $contentMd5 = CosUtil::getMd5SumForFile($uploadFile, $fromPos, $toPos);
            $upOptions[$cosClient::COS_CONTENT_MD5] = $contentMd5;
        }
        //2. 将每一分片上传到COS
        try {
            $responseUploadPart[] = $cosClient->uploadPart($bucket, $object, $uploadId, $upOptions);
        } catch (CosException $e) {
            printf(__FUNCTION__ . ": initiateMultipartUpload, uploadPart - part#{$i} FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
        printf(__FUNCTION__ . ": initiateMultipartUpload, uploadPart - part#{$i} OK\n");
    }
    $uploadParts = array();
    foreach ($responseUploadPart as $i => $eTag) {
        $uploadParts[] = array(
            'PartNumber' => strval($i + 1),
            'ETag' => $eTag,
        );
    }
    /**
     * step 3. 完成上传
     */
    try {
        $cosClient->completeMultipartUpload($bucket, $object, $uploadId, $uploadParts);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": completeMultipartUpload FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    printf(__FUNCTION__ . ": completeMultipartUpload OK\n");
}

/**
 * 按照目录上传文件
 *
 * @param CosClient $cosClient CosClient
 * @param string $bucket 存储空间名称
 *
 */
function uploadDir($cosClient, $bucket)
{
    $localDirectory = ".";
    $prefix = "samples/codes";
    try {
        $cosClient->uploadDir($bucket, $prefix, $localDirectory);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    printf(__FUNCTION__ . ": completeMultipartUpload OK\n");
}

/**
 * 获取当前未完成的分片上传列表
 *
 * @param $cosClient CosClient
 * @param $bucket   string
 */
function listMultipartUploads($cosClient, $bucket)
{
    $options = array(
        'max-uploads' => 100,
        'key-marker' => '',
        'prefix' => '',
        'upload-id-marker' => ''
    );
    try {
        $listMultipartUploadInfo = $cosClient->listMultipartUploads($bucket, $options);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": listMultipartUploads FAILED\n");
        printf($e->getMessage() . "\n");
        return;
    }
    printf(__FUNCTION__ . ": listMultipartUploads OK\n");
    $listUploadInfo = $listMultipartUploadInfo->getUploads();
    var_dump($listUploadInfo);
}
