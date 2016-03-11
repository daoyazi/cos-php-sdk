<?php

namespace COS\Result;

use COS\Core\CosException;

/**
 * Class UploadPartResult
 * @package COS\Result
 */
class UploadPartResult extends Result
{
    /**
     * 结果中part的ETag
     *
     * @return string
     * @throws CosException
     */
    protected function parseDataFromResponse()
    {
        $header = $this->rawResponse->header;
        if (isset($header["etag"])) {
            return $header["etag"];
        }
        throw new CosException("cannot get ETag");

    }
}
