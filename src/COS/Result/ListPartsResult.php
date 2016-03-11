<?php

namespace COS\Result;

use COS\Model\ListPartsInfo;
use COS\Model\PartInfo;


/**
 * Class ListPartsResult
 * @package COS\Result
 */
class ListPartsResult extends Result
{
    /**
     * 解析ListParts接口返回的json数据
     *
     * @return ListPartsInfo
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $resp = json_decode($content);
        $bucket = isset($resp->Bucket) ? strval($resp->Bucket) : "";
        $key = isset($resp->Key) ? strval($resp->Key) : "";
        $uploadId = isset($resp->UploadId) ? strval($resp->UploadId) : "";
        $nextPartNumberMarker = isset($resp->NextPartNumberMarker) ? intval($resp->NextPartNumberMarker) : "";
        $maxParts = isset($resp->MaxParts) ? intval($resp->MaxParts) : "";
        $isTruncated = isset($resp->IsTruncated) ? strval($resp->IsTruncated) : "";
        $partList = array();
        if (isset($resp->Parts)) {
            foreach ($resp->Parts as $part) {
                $partNumber = isset($part->PartNumber) ? intval($part->PartNumber) : "";
                $lastModified = isset($part->LastModified) ? strval($part->LastModified) : "";
                $eTag = isset($part->ETag) ? strval($part->ETag) : "";
                $size = isset($part->Size) ? intval($part->Size) : "";
                $partList[] = new PartInfo($partNumber, $lastModified, $eTag, $size);
            }
        }
        return new ListPartsInfo($bucket, $key, $uploadId, $nextPartNumberMarker, $maxParts, $isTruncated, $partList);
    }
}
