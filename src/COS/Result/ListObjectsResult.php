<?php

namespace COS\Result;

use COS\Core\CosUtil;
use COS\Model\ObjectInfo;
use COS\Model\ObjectListInfo;
use COS\Model\PrefixInfo;

/**
 * Class ListObjectsResult
 * @package COS\Result
 */
class ListObjectsResult extends Result
{
    /**
     * 解析ListObjects接口返回的json数据
     *
     * return ObjectListInfo
     */
    protected function parseDataFromResponse()
    {
        $resp = json_decode($this->rawResponse->body);
        $encodingType = isset($resp->EncodingType) ? strval($resp->EncodingType) : "";
        $objectList = $this->parseObjectList($resp, $encodingType);
        $prefixList = $this->parsePrefixList($resp, $encodingType);
        $bucketName = isset($resp->Name) ? strval($resp->Name) : "";
        $prefix = isset($resp->Prefix) ? strval($resp->Prefix) : "";
        $prefix = CosUtil::decodeKey($prefix, $encodingType);
        $marker = isset($resp->Marker) ? strval($resp->Marker) : "";
        $marker = CosUtil::decodeKey($marker, $encodingType);
        $maxKeys = isset($resp->MaxKeys) ? intval($resp->MaxKeys) : 0;
        $delimiter = isset($resp->Delimiter) ? strval($resp->Delimiter) : "";
        $delimiter = CosUtil::decodeKey($delimiter, $encodingType);
        $isTruncated = isset($resp->IsTruncated) ? strval($resp->IsTruncated) : "";
        $nextMarker = isset($resp->NextMarker) ? strval($resp->NextMarker) : "";
        $nextMarker = CosUtil::decodeKey($nextMarker, $encodingType);
        return new ObjectListInfo($bucketName, $prefix, $marker, $nextMarker, $maxKeys, $delimiter, $isTruncated, $objectList, $prefixList);
    }

    private function parseObjectList($resp, $encodingType)
    {
        $retList = array();
        if (isset($resp->Contents)) {
            foreach ($resp->Contents as $content) {
                $key = isset($content->Key) ? strval($content->Key) : "";
                $key = CosUtil::decodeKey($key, $encodingType);
                $lastModified = isset($content->LastModified) ? strval($content->LastModified) : "";
                $eTag = isset($content->ETag) ? strval($content->ETag) : "";
                $type = isset($content->Type) ? strval($content->Type) : "";
                $size = isset($content->Size) ? intval($content->Size) : 0;
                $storageClass = isset($content->StorageClass) ? strval($content->StorageClass) : "";
                $retList[] = new ObjectInfo($key, $lastModified, $eTag, $type, $size, $storageClass);
            }
        }
        return $retList;
    }

    private function parsePrefixList($resp, $encodingType)
    {
        $retList = array();
        if (isset($resp->CommonPrefixes)) {
            foreach ($resp->CommonPrefixes as $commonPrefix) {
                $prefix = isset($commonPrefix->Prefix) ? strval($commonPrefix->Prefix) : "";
                $prefix = CosUtil::decodeKey($prefix, $encodingType);
                $retList[] = new PrefixInfo($prefix);
            }
        }
        return $retList;
    }
}
