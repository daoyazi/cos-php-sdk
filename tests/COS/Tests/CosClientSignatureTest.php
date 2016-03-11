<?php

namespace COS\Tests;

use COS\Core\CosException;
use COS\Http\RequestCore;
use COS\Http\ResponseCore;
use COS\CosClient;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestCosClientBase.php';


class CosClientSignatureTest extends TestCosClientBase
{
    function testGetSignedUrlForGettingObject()
    {
        $object = "a.file";
        $this->cosClient->putObject($this->bucket, $object, file_get_contents(__FILE__));
        $timeout = 3600;
        try {
            $signedUrl = $this->cosClient->signUrl($this->bucket, $object, $timeout);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }

        $request = new RequestCore($signedUrl);
        $request->set_method('GET');
        $request->add_header('Content-Type', '');
        $request->send_request();
        $res = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
        $this->assertEquals(file_get_contents(__FILE__), $res->body);
    }

    public function testGetSignedUrlForPuttingObject()
    {
        $object = "a.file";
        $timeout = 3600;
        try {
            $signedUrl = $this->cosClient->signUrl($this->bucket, $object, $timeout, "PUT");
            $content = file_get_contents(__FILE__);
            $request = new RequestCore($signedUrl);
            $request->set_method('PUT');
            $request->add_header('Content-Type', '');
            $request->add_header('Content-Length', strlen($content));
            $request->set_body($content);
            $request->send_request();
            $res = new ResponseCore($request->get_response_header(),
                $request->get_response_body(), $request->get_response_code());
            $this->assertTrue($res->isOK());
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
    }

    public function testGetSignedUrlForPuttingObjectFromFile()
    {
        $file = __FILE__;
        $object = "a.file";
        $timeout = 3600;
        $options = array('Content-Type' => 'txt');
        try {
            $signedUrl = $this->cosClient->signUrl($this->bucket, $object, $timeout, "PUT", $options);
            $request = new RequestCore($signedUrl);
            $request->set_method('PUT');
            $request->add_header('Content-Type', 'txt');
            $request->set_read_file($file);
            $request->set_read_stream_size(filesize($file));
            $request->send_request();
            $res = new ResponseCore($request->get_response_header(),
                $request->get_response_body(), $request->get_response_code());
            $this->assertTrue($res->isOK());
        } catch (CosException $e) {
            $this->assertFalse(true);
        }

    }

    public function tearDown()
    {
        $this->cosClient->deleteObject($this->bucket, "a.file");
        parent::tearDown();
    }

    public function setUp()
    {
        parent::setUp();
        /**
         *  上传本地变量到bucket
         */
        $object = "a.file";
        $content = file_get_contents(__FILE__);
        $options = array(
            CosClient::COS_LENGTH => strlen($content),
            CosClient::COS_HEADERS => array(
                'Expires' => 'Fri, 28 Feb 2020 05:38:42 GMT',
                'Cache-Control' => 'no-cache',
                'Content-Disposition' => 'attachment;filename=cos_download.log',
                'Content-Encoding' => 'utf-8',
                'Content-Language' => 'zh-CN',
                'x-cos-server-side-encryption' => 'AES256',
                'x-cos-meta-self-define-title' => 'user define meta info',
            ),
        );

        try {
            $this->cosClient->putObject($this->bucket, $object, $content, $options);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
    }
}
