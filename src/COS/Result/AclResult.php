<?php

namespace COS\Result;

use COS\Core\CosException;
use COS\Tests\CosExceptionTest;

/**
 * Class AclResult getBucketAcl接口返回结果类，封装了
 * 返回的xml数据的解析
 *
 * @package COS\Result
 */
class AclResult extends Result
{
    /**
     * @return string
     * @throws CosException
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        if (empty($content)) {
            throw new CosException("body is null");
        }

        $response = json_decode($content);
        if (property_exists($response, "AccessControlList")
            && property_exists($response->AccessControlList, "Grant")) {
            return strval($response->AccessControlList->Grant);
        } else {
            throw new CosException("json format exception");
        }
    }
}
