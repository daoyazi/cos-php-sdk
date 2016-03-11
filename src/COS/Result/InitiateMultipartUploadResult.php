<?php

namespace COS\Result;

use COS\Core\CosException;


/**
 * Class initiateMultipartUploadResult
 * @package COS\Result
 */
class InitiateMultipartUploadResult extends Result
{
    /**
     * 结果中获取uploadId并返回
     *
     * @throws CosException
     * @return string
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $response = json_decode($content);
        if (property_exists($response, "UploadId")) {
            return strval($response->UploadId);
        }
        throw new CosException("cannot get UploadId");
    }
}
