<?php

namespace COS\Tests;


use COS\Core\CosException;
use COS\Core\CosUtil;

class CosUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testIsChinese()
    {
        $this->assertEquals(CosUtil::chkChinese("hello,world"), 0);
        $str = '你好,这里是卖咖啡!';
        $strGBK = CosUtil::encodePath($str);
        $this->assertEquals(CosUtil::chkChinese($str), 1);
        $this->assertEquals(CosUtil::chkChinese($strGBK), 1);
    }

    public function testIsGB2312()
    {
        $str = '你好,这里是卖咖啡!';
        $this->assertFalse(CosUtil::isGb2312($str));
    }

    public function testCheckChar()
    {
        $str = '你好,这里是卖咖啡!';
        $this->assertFalse(CosUtil::checkChar($str));
        $this->assertTrue(CosUtil::checkChar(iconv("UTF-8", "GB2312//IGNORE", $str)));
    }

    public function testIsIpFormat()
    {
        $this->assertTrue(CosUtil::isIPFormat("10.101.160.147"));
        $this->assertTrue(CosUtil::isIPFormat("12.12.12.34"));
        $this->assertTrue(CosUtil::isIPFormat("12.12.12.12"));
        $this->assertTrue(CosUtil::isIPFormat("255.255.255.255"));
        $this->assertTrue(CosUtil::isIPFormat("0.1.1.1"));
        $this->assertFalse(CosUtil::isIPFormat("0.1.1.x"));
        $this->assertFalse(CosUtil::isIPFormat("0.1.1.256"));
        $this->assertFalse(CosUtil::isIPFormat("256.1.1.1"));
        $this->assertFalse(CosUtil::isIPFormat("0.1.1.0.1"));
        $this->assertTrue(CosUtil::isIPFormat("10.10.10.10:123"));
    }

    public function testToQueryString()
    {
        $option = array("a" => "b");
        $this->assertEquals('a=b', CosUtil::toQueryString($option));
    }

    public function testSReplace()
    {
        $str = "<>&'\"";
        $this->assertEquals("&amp;lt;&amp;gt;&amp;&apos;&quot;", CosUtil::sReplace($str));
    }

    public function testCheckChinese()
    {
        $str = '你好,这里是卖咖啡!';
        $this->assertEquals(CosUtil::chkChinese($str), 1);
        if (CosUtil::isWin()) {
            $strGB = CosUtil::encodePath($str);
            $this->assertEquals($str, iconv("GB2312", "UTF-8", $strGB));
        }
    }

    public function testValidateOption()
    {
        $option = 'string';

        try {
            CosUtil::validateOptions($option);
            $this->assertFalse(true);
        } catch (CosException $e) {
            $this->assertEquals("string:option must be array", $e->getMessage());
        }

        $option = null;

        try {
            CosUtil::validateOptions($option);
            $this->assertTrue(true);
        } catch (CosException $e) {
            $this->assertFalse(true);
        }

    }

    public function testcreateDeleteObjectsJsonBody()
    {
        $json = <<<BBBB
{"Quiet":"true","Objects":[{"Key":"obj1"}]}
BBBB;
        $a = array('obj1');
        $this->assertEquals($json, $this->cleanJson(CosUtil::createDeleteObjectsJsonBody($a, 'true')));
    }

    public function testCreateCompleteMultipartUploadJsonBody()
    {
        $json = <<<BBBB
{"Parts":[{"PartNumber":2,"ETag":"xx"}]}
BBBB;
        $a = array(array("PartNumber" => 2, "ETag" => "xx"));
        $this->assertEquals($this->cleanJson(CosUtil::createCompleteMultipartUploadJsonBody($a)), $json);
    }

    public function testValidateBucket()
    {
        $this->assertTrue(CosUtil::validateBucket("xxx"));
        $this->assertFalse(CosUtil::validateBucket("XXXqwe123"));
        $this->assertFalse(CosUtil::validateBucket("XX"));
        $this->assertFalse(CosUtil::validateBucket("/X"));
        $this->assertFalse(CosUtil::validateBucket(""));
    }

    public function testValidateObject()
    {
        $this->assertTrue(CosUtil::validateObject("xxx"));
        $this->assertTrue(CosUtil::validateObject("xxx23"));
        $this->assertTrue(CosUtil::validateObject("12321-xxx"));
        $this->assertTrue(CosUtil::validateObject("x"));
        $this->assertFalse(CosUtil::validateObject("/aa"));
        $this->assertFalse(CosUtil::validateObject("\\aa"));
        $this->assertFalse(CosUtil::validateObject(""));
    }

    public function testStartWith()
    {
        $this->assertTrue(CosUtil::startsWith("xxab", "xx"), true);
    }

    public function testReadDir()
    {
        $list = CosUtil::readDir("./src", ".|..|.svn|.git", true);
        $this->assertNotNull($list);
    }

    public function testIsWin()
    {
        //$this->assertTrue(CosUtil::isWin());
    }

    public function testGetMd5SumForFile()
    {
        $this->assertEquals(CosUtil::getMd5SumForFile(__FILE__, 0, filesize(__FILE__) - 1), md5(file_get_contents(__FILE__)));
    }

    public function testGenerateFile()
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . "generatedFile.txt";
        CosUtil::generateFile($path, 1024 * 1024);
        $this->assertEquals(filesize($path), 1024 * 1024);
        unlink($path);
    }

    public function testThrowCosExceptionWithMessageIfEmpty()
    {
        $null = null;
        try {
            CosUtil::throwCosExceptionWithMessageIfEmpty($null, "xx");
            $this->assertTrue(false);
        } catch (CosException $e) {
            $this->assertEquals('xx', $e->getMessage());
        }
    }

    public function testThrowCosExceptionWithMessageIfEmpty2()
    {
        $null = "";
        try {
            CosUtil::throwCosExceptionWithMessageIfEmpty($null, "xx");
            $this->assertTrue(false);
        } catch (CosException $e) {
            $this->assertEquals('xx', $e->getMessage());
        }
    }

    public function testValidContent()
    {
        $null = "";
        try {
            CosUtil::validateContent($null);
            $this->assertTrue(false);
        } catch (CosException $e) {
            $this->assertEquals('http body content is invalid', $e->getMessage());
        }

        $notnull = "x";
        try {
            CosUtil::validateContent($notnull);
            $this->assertTrue(true);
        } catch (CosException $e) {
            $this->assertEquals('http body content is invalid', $e->getMessage());
        }
    }

    public function testThrowCosExceptionWithMessageIfEmpty3()
    {
        $null = "xx";
        try {
            CosUtil::throwCosExceptionWithMessageIfEmpty($null, "xx");
            $this->assertTrue(True);
        } catch (CosException $e) {
            $this->assertTrue(false);
        }
    }

    private function cleanJson($json)
    {
        return str_replace("\n", "", str_replace("\r", "", $json));
    }
}
