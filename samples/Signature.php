<?php
require_once __DIR__ . '/Common.php';

use COS\Http\RequestCore;
use COS\Http\ResponseCore;
use COS\CosClient;
use COS\Core\CosException;

$bucket = Common::getBucketName();
$cosClient = Common::getCosClient();
if (is_null($cosClient)) exit(1);

getSignedUrlForPuttingObject($cosClient, $bucket);
getSignedUrlForPuttingObjectFromFile($cosClient, $bucket);
getSignedUrlForGettingObject($cosClient, $bucket);


/**
 * 生成GetObject的签名url,主要用于私有权限下的读访问控制
 *
 * @param $cosClient CosClient CosClient实例
 * @param $bucket string 存储空间名称
 * @return null
 */
function getSignedUrlForGettingObject($cosClient, $bucket)
{
    $object = "test/test-signature-test-upload-and-download.txt";
    $timeout = 3600;
    try {
        $signedUrl = $cosClient->signUrl($bucket, $object, $timeout);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        assert(0);
    }
    print(__FUNCTION__ . ": signedUrl: " . $signedUrl . "\n");
    /**
     * 可以类似的代码来访问签名的URL，也可以输入到浏览器中去访问
     */
    $request = new RequestCore($signedUrl);
    $request->set_method('GET');
    $request->add_header('Content-Type', '');
    $request->send_request();
    $res = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
    if ($res->isOK()) {
        print(__FUNCTION__ . ": OK" . "\n");
    } else {
        print(__FUNCTION__ . ": FAILED" . "\n");
        assert(0);
    };
}

/**
 * 生成PutObject的签名url,主要用于私有权限下的写访问控制
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @return null
 * @throws CosException
 */
function getSignedUrlForPuttingObject($cosClient, $bucket)
{
    $object = "test/test-signature-test-upload-and-download.txt";
    $timeout = 3600;
    $options = NULL;
    try {
        $signedUrl = $cosClient->signUrl($bucket, $object, $timeout, "PUT");
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        assert(0);
    }
    print(__FUNCTION__ . ": signedUrl: " . $signedUrl . "\n");
    $content = file_get_contents(__FILE__);

    $request = new RequestCore($signedUrl);
    $request->set_method('PUT');
    $request->add_header('Content-Type', '');
    $request->add_header('Content-Length', strlen($content));
    $request->set_body($content);
    $request->send_request();
    $res = new ResponseCore($request->get_response_header(),
        $request->get_response_body(), $request->get_response_code());
    if ($res->isOK()) {
        print(__FUNCTION__ . ": OK" . "\n");
    } else {
        print(__FUNCTION__ . ": FAILED" . "\n");
        assert(0);
    };
}

/**
 * 生成PutObject的签名url,主要用于私有权限下的写访问控制， 用户可以利用生成的signedUrl
 * 从文件上传文件
 *
 * @param CosClient $cosClient CosClient实例
 * @param string $bucket 存储空间名称
 * @throws CosException
 */
function getSignedUrlForPuttingObjectFromFile($cosClient, $bucket)
{
    $file = __FILE__;
    $object = "test/test-signature-test-upload-and-download.txt";
    $timeout = 3600;
    $options = array('Content-Type' => 'txt');
    try {
        $signedUrl = $cosClient->signUrl($bucket, $object, $timeout, "PUT", $options);
    } catch (CosException $e) {
        printf(__FUNCTION__ . ": FAILED\n");
        printf($e->getMessage() . "\n");
        assert(0);
    }
    print(__FUNCTION__ . ": signedUrl: " . $signedUrl . "\n");

    $request = new RequestCore($signedUrl);
    $request->set_method('PUT');
    $request->add_header('Content-Type', 'txt');
    $request->set_read_file($file);
    $request->set_read_stream_size(filesize($file));
    $request->send_request();
    $res = new ResponseCore($request->get_response_header(),
        $request->get_response_body(), $request->get_response_code());
    if ($res->isOK()) {
        print(__FUNCTION__ . ": OK" . "\n");
    } else {
        print(__FUNCTION__ . ": FAILED" . "\n");
        assert(0);
    };
}
