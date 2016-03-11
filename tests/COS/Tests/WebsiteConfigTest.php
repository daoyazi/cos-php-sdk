<?php

namespace COS\Tests;


use COS\Model\WebsiteConfig;
use COS\Core\CosException;

class WebsiteConfigTest extends \PHPUnit_Framework_TestCase
{
    private $validXml = <<<BBBB
{
    "IndexDocument":{
        "Suffix": "index.html"
    },
    "ErrorDocument":{
        "Key": "errorDocument.html"
    }
}
BBBB;

    private $nullXml = <<<BBBB
{
    "IndexDocument":{
        "Suffix": ""
    },
    "ErrorDocument":{
        "Key": ""
    }
}
BBBB;

    private $nullXml2 = <<<BBBB
{
    "IndexDocument":{
        "Suffix": ""
    },
    "ErrorDocument":{
        "Key": ""
    }
}
BBBB;

    public function testParseValidXml()
    {
        $websiteConfig = new WebsiteConfig("index");
        $websiteConfig->parseFromJson($this->validXml);
        $this->assertEquals($this->cleanJson($this->validXml), $this->cleanJson($websiteConfig->serializeToJson()));
    }

    public function testParsenullXml()
    {
        $websiteConfig = new WebsiteConfig();
        $websiteConfig->parseFromJson($this->nullXml);
        $this->assertTrue($this->cleanJson($this->nullXml) === $this->cleanJson($websiteConfig->serializeToJson()) ||
            $this->cleanJson($this->nullXml2) === $this->cleanJson($websiteConfig->serializeToJson()));
    }

    public function testWebsiteConstruct()
    {
        $websiteConfig = new WebsiteConfig("index.html", "errorDocument.html");
        $this->assertEquals('index.html', $websiteConfig->getIndexDocument());
        $this->assertEquals('errorDocument.html', $websiteConfig->getErrorDocument());
        $this->assertEquals($this->cleanJson($this->validXml), $this->cleanJson($websiteConfig->serializeToJson()));
    }

    private function cleanJson($json)
    {
        return str_replace("\n", "", str_replace("\r", "", str_replace(" ", "", $json)));
    }
}
