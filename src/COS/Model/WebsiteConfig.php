<?php

namespace COS\Model;


use COS\Core\CosException;


/**
 * Class WebsiteConfig
 * @package COS\Model
 */
class WebsiteConfig implements JsonConfig
{
    /**
     * WebsiteConfig constructor.
     * @param  string $indexDocument
     * @param  string $errorDocument
     */
    public function __construct($indexDocument = "", $errorDocument = "")
    {
        $this->indexDocument = $indexDocument;
        $this->errorDocument = $errorDocument;
    }

    /**
     * @param string $strJson
     * @return null
     */
    public function parseFromJson($strJson)
    {
        $json = json_decode($strJson);
        if (isset($json->IndexDocument) && isset($json->IndexDocument->Suffix)) {
            $this->indexDocument = strval($json->IndexDocument->Suffix);
        }
        if (isset($json->ErrorDocument) && isset($json->ErrorDocument->Key)) {
            $this->errorDocument = strval($json->ErrorDocument->Key);
        }
    }

    /**
     * 把WebsiteConfig序列化成json
     *
     * @return string
     * @throws CosException
     */
    public function serializeToJson()
    {
        $body = '{';
        $body = $body . '"IndexDocument"' . ':' . '{' . '"Suffix"' . ':' . json_encode($this->indexDocument) . '}' . ',';
        $body = $body . '"ErrorDocument"' . ':' . '{' . '"Key"' . ':' . json_encode($this->errorDocument) . '}';
        $body = $body . '}';

        return $body;
    }

    /**
     * @return string
     */
    public function getIndexDocument()
    {
        return $this->indexDocument;
    }

    /**
     * @return string
     */
    public function getErrorDocument()
    {
        return $this->errorDocument;
    }

    private $indexDocument = "";
    private $errorDocument = "";
}
