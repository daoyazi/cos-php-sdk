<?php

namespace COS\Tests;

use COS\Core\CosException;
use COS\CosClient;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestCosClientBase.php';


class CosClientObjectTest extends TestCosClientBase
{

    public function testGetObjectWithHeader()
    {
        $object = "cos-php-sdk-test/upload-test-object-name.txt";
        try {
            $res = $this->cosClient->getObject($this->bucket, $object, array(CosClient::COS_LAST_MODIFIED => "xx"));
            $this->assertEquals(file_get_contents(__FILE__), $res);
        } catch (CosException $e) {
            $this->assertEquals('"/ilegal.txt" object name is invalid', $e->getMessage());
        }
    }

    public function testGetObjectWithIleggalEtag()
    {
        $object = "cos-php-sdk-test/upload-test-object-name.txt";
        try {
            $res = $this->cosClient->getObject($this->bucket, $object, array(CosClient::COS_ETAG => "xx"));
            $this->assertEquals(file_get_contents(__FILE__), $res);
        } catch (CosException $e) {
            $this->assertEquals('"/ilegal.txt" object name is invalid', $e->getMessage());
        }
    }

    public function testPutIllelObject()
    {
        $object = "/ilegal.txt";
        try {
            $this->cosClient->putObject($this->bucket, $object, "hi", null);
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals('"/ilegal.txt" object name is invalid', $e->getMessage());
        }
    }

    public function testObject()
    {
        /**
         *  上传本地变量到bucket
         */
        $object = "cos-php-sdk-test/upload-test-object-name.txt";
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

        try {
            $this->cosClient->deleteObjects($this->bucket, "stringtype", $options);
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals('objects must be array', $e->getMessage());
        }

        try {
            $this->cosClient->uploadFile($this->bucket, $object, "notexist.txt", $options);
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals('notexist.txt file does not exist', $e->getMessage());
        }

        /**
         * getObject到本地变量，检查是否match
         */
        try {
            $content = $this->cosClient->getObject($this->bucket, $object);
            $this->assertEquals($content, file_get_contents(__FILE__));
        } catch (CosException $e) {
            $this->assertFalse(true);
        }

        /**
         * getObject的前五个字节
         */
        try {
            $options = array(CosClient::COS_RANGE => '0-4');
            $content = $this->cosClient->getObject($this->bucket, $object, $options);
            $this->assertEquals($content, '<?php');
        } catch (CosException $e) {
            $this->assertFalse(true);
        }


        /**
         * 上传本地文件到object
         */
        try {
            $this->cosClient->uploadFile($this->bucket, $object, __FILE__);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }

        /**
         * 下载文件到本地变量，检查是否match
         */
        try {
            $content = $this->cosClient->getObject($this->bucket, $object);
            $this->assertEquals($content, file_get_contents(__FILE__));
        } catch (CosException $e) {
            $this->assertFalse(true);
        }

        /**
         * 下载文件到本地文件
         */
        $localfile = "upload-test-object-name.txt";
        $options = array(
            CosClient::COS_FILE_DOWNLOAD => $localfile,
        );

        try {
            $this->cosClient->getObject($this->bucket, $object, $options);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
        $this->assertTrue(file_get_contents($localfile) === file_get_contents(__FILE__));
        if (file_exists($localfile)) {
            unlink($localfile);
        }

        // /**
        //  * 复制object
        //  */
        // $to_bucket = $this->bucket;
        // $to_object = $object . '.copy';
        // $options = array();
        // try {
        //     $this->cosClient->copyObject($this->bucket, $object, $to_bucket, $to_object, $options);
        // } catch (CosException $e) {
        //     $this->assertFalse(true);
        //     var_dump($e->getMessage());

        // }

        // /**
        //  * 检查复制的是否相同
        //  */
        // try {
        //     echo "filename is:", $to_object, "\n";
        //     $content = $this->cosClient->getObject($this->bucket, $to_object);
        //     $this->assertEquals($content, file_get_contents(__FILE__));
        // } catch (CosException $e) {
        //     $this->assertFalse(true);
        // }

        /**
         * 列出bucket内的文件列表
         */
        $prefix = '';
        $delimiter = '/';
        $next_marker = '';
        $maxkeys = 1000;
        $options = array(
            'delimiter' => $delimiter,
            'prefix' => $prefix,
            'max-keys' => $maxkeys,
            'marker' => $next_marker,
        );

        try {
            $listObjectInfo = $this->cosClient->listObjects($this->bucket, $options);
            $objectList = $listObjectInfo->getObjectList();
            $prefixList = $listObjectInfo->getPrefixList();
            $this->assertNotNull($objectList);
            $this->assertNotNull($prefixList);
            $this->assertTrue(is_array($objectList));
            $this->assertTrue(is_array($prefixList));

        } catch (CosException $e) {
            $this->assertTrue(false);
        }

        // /**
        //  * 设置文件的meta信息
        //  */
        // $from_bucket = $this->bucket;
        // $from_object = "cos-php-sdk-test/upload-test-object-name.txt";
        // $to_bucket = $from_bucket;
        // $to_object = $from_object;
        // $copy_options = array(
        //     CosClient::COS_HEADERS => array(
        //         'Expires' => '2012-10-01 08:00:00',
        //         'Content-Disposition' => 'attachment; filename="xxxxxx"',
        //     ),
        // );
        // try {
        //     $this->cosClient->copyObject($from_bucket, $from_object, $to_bucket, $to_object, $copy_options);
        // } catch (CosException $e) {
        //     $this->assertFalse(true);
        // }

        // /**
        //  * 获取文件的meta信息
        //  */
        // $object = "cos-php-sdk-test/upload-test-object-name.txt";
        // try {
        //     $objectMeta = $this->cosClient->getObjectMeta($this->bucket, $object);
        //     $this->assertEquals('attachment; filename="xxxxxx"', $objectMeta[strtolower('Content-Disposition')]);
        // } catch (CosException $e) {
        //     $this->assertFalse(true);
        // }

        /**
         *  删除单个文件
         */
        $object = "cos-php-sdk-test/upload-test-object-name.txt";

        try {
            $this->assertTrue($this->cosClient->doesObjectExist($this->bucket, $object));
            $this->cosClient->deleteObject($this->bucket, $object);
            $this->assertFalse($this->cosClient->doesObjectExist($this->bucket, $object));
        } catch (CosException $e) {
            $this->assertFalse(true);
        }

        /**
         *  删除多个个文件
         */
        $object1 = "cos-php-sdk-test/upload-test-object-name.txt";
        $object2 = "cos-php-sdk-test/upload-test-object-name.txt.copy";
        $list = array($object1);
        try {
            $this->assertFalse($this->cosClient->doesObjectExist($this->bucket, $object1));
            // $this->cosClient->deleteObjects($this->bucket, $list, array('quiet' => true));
            // $this->cosClient->deleteObjects($this->bucket, $list, array('quiet' => 'true'));
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
    }

    public function setUp()
    {
        parent::setUp();
        $this->cosClient->putObject($this->bucket, 'cos-php-sdk-test/upload-test-object-name.txt', file_get_contents(__FILE__));
    }
}
