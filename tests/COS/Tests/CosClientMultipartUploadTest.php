<?php

namespace COS\Tests;

use COS\Core\CosException;
use COS\CosClient;
use COS\Core\CosUtil;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestCosClientBase.php';


class CosClientMultipartUploadTest extends TestCosClientBase
{
    public function testInvalidDir()
    {
        try {
            $this->cosClient->uploadDir($this->bucket, "", "abc/ds/s/s/notexitst");
            $this->assertFalse(true);

        } catch (CosException $e) {
            $this->assertEquals("parameter error: abc/ds/s/s/notexitst is not a directory, please check it", $e->getMessage());
        }

    }

    public function testMultipartUploadBigFile()
    {
        $bigFileName = __DIR__ . DIRECTORY_SEPARATOR . "bigfile.tmp";
        $localFilename = __DIR__ . DIRECTORY_SEPARATOR . "localfile.tmp";
        CosUtil::generateFile($bigFileName, 6 * 1024 * 1024);
        $object = 'mpu/multipart-bigfile-test.tmp';
        try {
            $this->cosClient->multiuploadFile($this->bucket, $object, $bigFileName, array(CosClient::COS_PART_SIZE => 1));
            $options = array(CosClient::COS_FILE_DOWNLOAD => $localFilename);
            $this->cosClient->getObject($this->bucket, $object, $options);

            $this->assertEquals(md5_file($bigFileName), md5_file($localFilename));
        } catch (CosException $e) {
            var_dump($e->getMessage());
            $this->assertFalse(true);
        }
        unlink($bigFileName);
        unlink($localFilename);
    }

    // public function testCopyPart()
    // {
    //     $object = "mpu/multipart-test.txt";
    //     $copiedObject = "mpu/multipart-test.txt.copied";
    //     $this->cosClient->putObject($this->bucket, $copiedObject, file_get_contents(__FILE__));
    //     /**
    //      *  step 1. 初始化一个分块上传事件, 也就是初始化上传Multipart, 获取upload id
    //      */
    //     try {
    //         $upload_id = $this->cosClient->initiateMultipartUpload($this->bucket, $object);
    //     } catch (CosException $e) {
    //         $this->assertFalse(true);
    //     }
    //     /*
    //      * step 2. uploadPartCopy
    //      */
    //     $copyId = 1;
    //     $eTag = $this->cosClient->uploadPartCopy($this->bucket, $copiedObject, $this->bucket, $object, $copyId, $upload_id);
    //     $upload_parts[] = array(
    //         'PartNumber' => $copyId,
    //         'ETag' => $eTag,
    //     );

    //     try {
    //         $listPartsInfo = $this->cosClient->listParts($this->bucket, $object, $upload_id);
    //         $this->assertNotNull($listPartsInfo);
    //     } catch (CosException $e) {
    //         $this->assertTrue(false);
    //     }

    //     /**
    //      * step 3.
    //      */
    //     try {
    //         $this->cosClient->completeMultipartUpload($this->bucket, $object, $upload_id, $upload_parts);
    //     } catch (CosException $e) {
    //         var_dump($e->getMessage());
    //         $this->assertTrue(false);
    //     }

    //     $this->assertEquals($this->cosClient->getObject($this->bucket, $object), file_get_contents(__FILE__));
    //     $this->assertEquals($this->cosClient->getObject($this->bucket, $copiedObject), file_get_contents(__FILE__));
    // }

    public function testAbortMultipartUpload()
    {
        $object = "mpu/multipart-test.txt";
        /**
         *  step 1. 初始化一个分块上传事件, 也就是初始化上传Multipart, 获取upload id
         */
        try {
            $upload_id = $this->cosClient->initiateMultipartUpload($this->bucket, $object);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
        /*
         * step 2. 上传分片
         */
        $part_size = 10 * 1024 * 1024;
        $upload_file = __FILE__;
        $upload_filesize = filesize($upload_file);
        $pieces = $this->cosClient->generateMultiuploadParts($upload_filesize, $part_size);
        $response_upload_part = array();
        $upload_position = 0;
        $is_check_md5 = true;
        foreach ($pieces as $i => $piece) {
            $from_pos = $upload_position + (integer)$piece[CosClient::COS_SEEK_TO];
            $to_pos = (integer)$piece[CosClient::COS_LENGTH] + $from_pos - 1;
            $up_options = array(
                CosClient::COS_FILE_UPLOAD => $upload_file,
                CosClient::COS_PART_NUM => ($i + 1),
                CosClient::COS_SEEK_TO => $from_pos,
                CosClient::COS_LENGTH => $to_pos - $from_pos + 1,
                CosClient::COS_CHECK_MD5 => $is_check_md5,
            );
            if ($is_check_md5) {
                $content_md5 = CosUtil::getMd5SumForFile($upload_file, $from_pos, $to_pos);
                $up_options[CosClient::COS_CONTENT_MD5] = $content_md5;
            }
            //2. 将每一分片上传到COS
            try {
                $response_upload_part[] = $this->cosClient->uploadPart($this->bucket, $object, $upload_id, $up_options);
            } catch (CosException $e) {
                $this->assertFalse(true);
            }
        }
        $upload_parts = array();
        foreach ($response_upload_part as $i => $eTag) {
            $upload_parts[] = array(
                'PartNumber' => ($i + 1),
                'ETag' => $eTag,
            );
        }

        try {
            $listPartsInfo = $this->cosClient->listParts($this->bucket, $object, $upload_id);
            $this->assertNotNull($listPartsInfo);
        } catch (CosException $e) {
            $this->assertTrue(false);
        }
        $this->assertEquals(1, count($listPartsInfo->getListPart()));

        $numOfMultipartUpload1 = 0;
        $options = null;
        try {
            $listMultipartUploadInfo = $listMultipartUploadInfo = $this->cosClient->listMultipartUploads($this->bucket, $options);
            $this->assertNotNull($listMultipartUploadInfo);
            $numOfMultipartUpload1 = count($listMultipartUploadInfo->getUploads());
        } catch (CosException $e) {
            $this->assertFalse(true);
        }

        try {
            $this->cosClient->abortMultipartUpload($this->bucket, $object, $upload_id);
        } catch (CosException $e) {
            $this->assertTrue(false);
        }

        $numOfMultipartUpload2 = 0;
        try {
            $listMultipartUploadInfo = $listMultipartUploadInfo = $this->cosClient->listMultipartUploads($this->bucket, $options);
            $this->assertNotNull($listMultipartUploadInfo);
            $numOfMultipartUpload2 = count($listMultipartUploadInfo->getUploads());
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
        $this->assertEquals($numOfMultipartUpload1 - 1, $numOfMultipartUpload2);
    }

    public function testPutObjectByRawApis()
    {
        $object = "mpu/multipart-test.txt";
        /**
         *  step 1. 初始化一个分块上传事件, 也就是初始化上传Multipart, 获取upload id
         */
        try {
            $upload_id = $this->cosClient->initiateMultipartUpload($this->bucket, $object);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
        /*
         * step 2. 上传分片
         */
        $part_size = 10 * 1024 * 1024;
        $upload_file = __FILE__;
        $upload_filesize = filesize($upload_file);
        $pieces = $this->cosClient->generateMultiuploadParts($upload_filesize, $part_size);
        $response_upload_part = array();
        $upload_position = 0;
        $is_check_md5 = true;
        foreach ($pieces as $i => $piece) {
            $from_pos = $upload_position + (integer)$piece[CosClient::COS_SEEK_TO];
            $to_pos = (integer)$piece[CosClient::COS_LENGTH] + $from_pos - 1;
            $up_options = array(
                CosClient::COS_FILE_UPLOAD => $upload_file,
                CosClient::COS_PART_NUM => ($i + 1),
                CosClient::COS_SEEK_TO => $from_pos,
                CosClient::COS_LENGTH => $to_pos - $from_pos + 1,
                CosClient::COS_CHECK_MD5 => $is_check_md5,
            );
            if ($is_check_md5) {
                $content_md5 = CosUtil::getMd5SumForFile($upload_file, $from_pos, $to_pos);
                $up_options[CosClient::COS_CONTENT_MD5] = $content_md5;
            }
            //2. 将每一分片上传到COS
            try {
                $response_upload_part[] = $this->cosClient->uploadPart($this->bucket, $object, $upload_id, $up_options);
            } catch (CosException $e) {
                $this->assertFalse(true);
            }
        }
        $upload_parts = array();
        foreach ($response_upload_part as $i => $eTag) {
            $upload_parts[] = array(
                'PartNumber' => ($i + 1),
                'ETag' => $eTag,
            );
        }

        try {
            $listPartsInfo = $this->cosClient->listParts($this->bucket, $object, $upload_id);
            $this->assertNotNull($listPartsInfo);
        } catch (CosException $e) {
            $this->assertTrue(false);
        }

        /**
         * step 3.
         */
        try {
            $this->cosClient->completeMultipartUpload($this->bucket, $object, $upload_id, $upload_parts);
        } catch (CosException $e) {
            $this->assertTrue(false);
        }
    }

    function testPutObjectsByDir()
    {
        $localDirectory = dirname(__FILE__);
        $prefix = "samples/codes";
        try {
            $this->cosClient->uploadDir($this->bucket, $prefix, $localDirectory);
        } catch (CosException $e) {
            var_dump($e->getMessage());
            $this->assertFalse(true);

        }
        sleep(1);
        $this->assertTrue($this->cosClient->doesObjectExist($this->bucket, 'samples/codes/' . "CosClientMultipartUploadTest.php"));
    }

    public function testPutObjectByMultipartUpload()
    {
        $object = "mpu/multipart-test.txt";
        $file = __FILE__;
        $options = array();

        try {
            $this->cosClient->multiuploadFile($this->bucket, $object, $file, $options);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
    }

    public function testListMultipartUploads()
    {
        $options = null;
        try {
            $listMultipartUploadInfo = $listMultipartUploadInfo = $this->cosClient->listMultipartUploads($this->bucket, $options);
            $this->assertNotNull($listMultipartUploadInfo);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }
    }
}
