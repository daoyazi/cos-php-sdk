<?php

namespace COS\Result;

use COS\Core\CosUtil;
use COS\Model\ListMultipartUploadInfo;
use COS\Model\UploadInfo;


/**
 * Class ListMultipartUploadResult
 * @package COS\Result
 */
class ListMultipartUploadResult extends Result
{
    /**
     * 解析从ListMultipartUpload接口的返回数据
     *
     * @return ListMultipartUploadInfo
     */
    protected function parseDataFromResponse()
    {
        $content = $this->rawResponse->body;
        $resp = json_decode($content);

        $encodingType = isset($resp->EncodingType) ? strval($resp->EncodingType) : "";
        $bucket = isset($resp->Bucket) ? strval($resp->Bucket) : "";
        $keyMarker = isset($resp->KeyMarker) ? strval($resp->KeyMarker) : "";
        $keyMarker = CosUtil::decodeKey($keyMarker, $encodingType);
        $uploadIdMarker = isset($resp->UploadIdMarker) ? strval($resp->UploadIdMarker) : "";
        $nextKeyMarker = isset($resp->NextKeyMarker) ? strval($resp->NextKeyMarker) : "";
        $nextKeyMarker = CosUtil::decodeKey($nextKeyMarker, $encodingType);
        $nextUploadIdMarker = isset($resp->NextUploadIdMarker) ? strval($resp->NextUploadIdMarker) : "";
        $delimiter = isset($resp->Delimiter) ? strval($resp->Delimiter) : "";
        $delimiter = CosUtil::decodeKey($delimiter, $encodingType);
        $prefix = isset($resp->Prefix) ? strval($resp->Prefix) : "";
        $prefix = CosUtil::decodeKey($prefix, $encodingType);
        $maxUploads = isset($resp->MaxUploads) ? intval($resp->MaxUploads) : 0;
        $isTruncated = isset($resp->IsTruncated) ? strval($resp->IsTruncated) : "";
        $listUpload = array();

        if (isset($resp->Uploads)) {
            foreach ($resp->Uploads as $upload) {
                $key = isset($upload->Key) ? strval($upload->Key) : "";
                $key = CosUtil::decodeKey($key, $encodingType);
                $uploadId = isset($upload->UploadId) ? strval($upload->UploadId) : "";
                $initiated = isset($upload->Initiated) ? strval($upload->Initiated) : "";
                $listUpload[] = new UploadInfo($key, $uploadId, $initiated);
            }
        }
        return new ListMultipartUploadInfo($bucket, $keyMarker, $uploadIdMarker,
            $nextKeyMarker, $nextUploadIdMarker,
            $delimiter, $prefix, $maxUploads, $isTruncated, $listUpload);
    }
}
