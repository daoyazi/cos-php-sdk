<?php

namespace COS\Result;

use COS\Model\BucketInfo;
use COS\Model\BucketListInfo;

/**
 * Class ListBucketsResult
 *
 * @package COS\Result
 */
class ListBucketsResult extends Result
{
    /**
     * @return BucketListInfo
     */
    protected function parseDataFromResponse()
    {
        $bucketList = array();
        $content = $this->rawResponse->body;
        $response = json_decode($content);
        if ((property_exists($response, "Buckets"))) {
            foreach($response->Buckets as $bucket) {
                $bucketInfo = new BucketInfo(strval($bucket->Location),
                    strval($bucket->Name),
                    strval($bucket->CreationDate),
                    strval($bucket->ACL));
                $bucketList[] = $bucketInfo;
            }
        }
        return new BucketListInfo($bucketList);
    }
}
