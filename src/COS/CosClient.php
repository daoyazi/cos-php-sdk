<?php
namespace COS;

use COS\Core\MimeTypes;
use COS\Core\CosException;
use COS\Http\RequestCore;
use COS\Http\RequestCore_Exception;
use COS\Http\ResponseCore;
use COS\Model\CorsConfig;
use COS\Model\LoggingConfig;
use COS\Result\AclResult;
use COS\Result\BodyResult;
use COS\Result\GetCorsResult;
use COS\Result\GetLifecycleResult;
use COS\Result\GetLoggingResult;
use COS\Result\GetRefererResult;
use COS\Result\GetWebsiteResult;
use COS\Result\HeaderResult;
use COS\Result\InitiateMultipartUploadResult;
use COS\Result\ListBucketsResult;
use COS\Result\ListMultipartUploadResult;
use COS\Model\ListMultipartUploadInfo;
use COS\Result\ListObjectsResult;
use COS\Result\ListPartsResult;
use COS\Result\PutSetDeleteResult;
use COS\Result\ExistResult;
use COS\Model\ObjectListInfo;
use COS\Result\UploadPartResult;
use COS\Model\BucketListInfo;
use COS\Model\LifecycleConfig;
use COS\Model\RefererConfig;
use COS\Model\WebsiteConfig;
use COS\Core\CosUtil;
use COS\Model\ListPartsInfo;

/**
 * Class CosClient
 *
 * Object Storage Service(COS) 的客户端类，封装了用户通过COS API对COS服务的各种操作，
 * 用户通过CosClient实例可以进行Bucket，Object，MultipartUpload, ACL等操作，具体
 * 的接口规则可以参考官方COS API文档
 */
class CosClient
{
    /**
     * 构造函数
     *
     * 构造函数有几种情况：
     * 1. 一般的时候初始化使用 $cosClient = new CosClient($id, $key, $endpoint)
     * 2. 如果使用了华云SecurityTokenService(STS)，获得了AccessKeyID, AccessKeySecret, Token
     * 初始化使用  $cosClient = new CosClient($id, $key, $endpoint, $token)
     * 3. 如果用户使用的endpoint是ip
     * 初始化使用 $cosClient = new CosClient($id, $key, “1.2.3.4:8900”)
     *
     * @param string $accessKeyId 从COS获得的AccessKeyId
     * @param string $accessKeySecret 从COS获得的AccessKeySecret
     * @param string $endpoint 您选定的COS数据中心访问域名，例如cos-cn-hangzhou.chinac.com
     * @param string $securityToken
     * @throws CosException
     */
    public function __construct($accessKeyId, $accessKeySecret, $endpoint, $securityToken = NULL)
    {
        if (empty($accessKeyId)) {
            throw new CosException("access key id is empty");
        }
        if (empty($accessKeySecret)) {
            throw new CosException("access key secret is empty");
        }
        if (empty($endpoint)) {
            throw new CosException("endpoint is empty");
        }
        $this->hostname = $this->checkEndpoint($endpoint, false);
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->securityToken = $securityToken;
        self::checkEnv();
    }

    /**
     * 列举用户所有的Bucket[GetService]
     *
     * @param array $options
     * @throws CosException
     * @return BucketListInfo
     */
    public function listBuckets($options = NULL)
    {
        if ($this->hostType === self::COS_HOST_TYPE_CNAME) {
            throw new CosException("operation is not permitted with CName host");
        }
        $this->precheckOptions($options);
        $options[self::COS_BUCKET] = '';
        $options[self::COS_METHOD] = self::COS_HTTP_GET;
        $options[self::COS_OBJECT] = '/';
        $response = $this->auth($options);
        $result = new ListBucketsResult($response);
        return $result->getData();
    }

    /**
     * 创建bucket，默认创建的bucket的ACL是CosClient::COS_ACL_TYPE_PRIVATE
     *
     * @param string $bucket
     * @param string $acl
     * @param array $options
     * @return null
     */
    public function createBucket($bucket, $acl = self::COS_ACL_TYPE_PRIVATE, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_HEADERS] = array(self::COS_ACL => $acl);
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 删除bucket
     * 如果Bucket不为空（Bucket中有Object，或者有分块上传的碎片），则Bucket无法删除，
     * 必须删除Bucket中的所有Object以及碎片后，Bucket才能成功删除。
     *
     * @param string $bucket
     * @param array $options
     * @return null
     */
    public function deleteBucket($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_DELETE;
        $options[self::COS_OBJECT] = '/';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 判断bucket是否存在
     *
     * @param string $bucket
     * @return bool
     * @throws CosException
     */
    public function doesBucketExist($bucket)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_GET;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }

    /**
     * 获取bucket的ACL配置情况
     *
     * @param string $bucket
     * @param array $options
     * @throws CosException
     * @return string
     */
    public function getBucketAcl($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_GET;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new AclResult($response);
        return $result->getData();
    }

    /**
     * 设置bucket的ACL配置情况
     *
     * @param string $bucket bucket名称
     * @param string $acl 读写权限，可选值 ['private', 'public-read']
     * @param array $options 可以为空
     * @throws CosException
     * @return null
     */
    public function putBucketAcl($bucket, $acl, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_HEADERS] = array(self::COS_ACL => $acl);
        $options[self::COS_SUB_RESOURCE] = 'acl';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 将bucket设置成静态网站托管模式
     *
     * @param string $bucket bucket名称
     * @param WebsiteConfig $websiteConfig
     * @param array $options 可以为空
     * @throws CosException
     * @return null
     */
    public function putBucketWebsite($bucket, $websiteConfig, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_SUB_RESOURCE] = 'website';
        $options[self::COS_CONTENT_TYPE] = 'application/json';
        $options[self::COS_CONTENT] = $websiteConfig->serializeToJson();
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 获取bucket的静态网站托管状态
     *
     * @param string $bucket bucket名称
     * @param array $options
     * @throws CosException
     * @return WebsiteConfig
     */
    public function getBucketWebsite($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_GET;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_SUB_RESOURCE] = 'website';
        $response = $this->auth($options);
        $result = new GetWebsiteResult($response);
        return $result->getData();
    }

    /**
     * 关闭bucket的静态网站托管模式
     *
     * @param string $bucket bucket名称
     * @param array $options
     * @throws CosException
     * @return null
     */
    public function deleteBucketWebsite($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_DELETE;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_SUB_RESOURCE] = 'website';
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 获取bucket下的object列表
     *
     * @param string $bucket
     * @param array $options
     * 其中options中的参数如下
     * $options = array(
     *      'max-keys'  => max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000。
     *      'prefix'    => 限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix。
     *      'delimiter' => 是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
     *      'marker'    => 用户设定结果从marker之后按字母排序的第一个开始返回。
     *)
     * 其中 prefix，marker用来实现分页显示效果，参数的长度必须小于256字节。
     * @throws CosException
     * @return ObjectListInfo
     */
    public function listObjects($bucket, $options = NULL)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_GET;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_QUERY_STRING] = array(
            self::COS_DELIMITER => isset($options[self::COS_DELIMITER]) ? $options[self::COS_DELIMITER] : '/',
            self::COS_PREFIX => isset($options[self::COS_PREFIX]) ? $options[self::COS_PREFIX] : '',
            self::COS_MAX_KEYS => isset($options[self::COS_MAX_KEYS]) ? $options[self::COS_MAX_KEYS] : self::COS_MAX_KEYS_VALUE,
            self::COS_MARKER => isset($options[self::COS_MARKER]) ? $options[self::COS_MARKER] : '',
        );
        $response = $this->auth($options);
        $result = new ListObjectsResult($response);
        return $result->getData();
    }

    /**
     * 创建虚拟目录 (本函数会在object名称后增加'/', 所以创建目录的object名称不需要'/'结尾，否则，目录名称会变成'//')
     *
     * 暂不开放此接口
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param array $options
     * @return null
     */
    public function createObjectDir($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_OBJECT] = $object . '/';
        $options[self::COS_CONTENT_LENGTH] = array(self::COS_CONTENT_LENGTH => 0);
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 上传内存中的内容
     *
     * @param string $bucket bucket名称
     * @param string $object objcet名称
     * @param string $content 上传的内容
     * @param array $options
     * @return null
     */
    public function putObject($bucket, $object, $content, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);

        CosUtil::validateContent($content);
        $options[self::COS_CONTENT] = $content;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_OBJECT] = $object;

        if (!isset($options[self::COS_LENGTH])) {
            $options[self::COS_CONTENT_LENGTH] = strlen($options[self::COS_CONTENT]);
        } else {
            $options[self::COS_CONTENT_LENGTH] = $options[self::COS_LENGTH];
        }

        if (!isset($options[self::COS_CONTENT_TYPE])) {
            $options[self::COS_CONTENT_TYPE] = $this->getMimeType($object);
        }
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 上传本地文件
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param string $file 本地文件路径
     * @param array $options
     * @return null
     * @throws CosException
     */
    public function uploadFile($bucket, $object, $file, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        CosUtil::throwCosExceptionWithMessageIfEmpty($file, "file path is invalid");
        $file = CosUtil::encodePath($file);
        if (!file_exists($file)) {
            throw new CosException($file . " file does not exist");
        }
        $options[self::COS_FILE_UPLOAD] = $file;
        $file_size = filesize($options[self::COS_FILE_UPLOAD]);
        $is_check_md5 = $this->isCheckMD5($options);
        if ($is_check_md5) {
            $content_md5 = md5_file($options[self::COS_FILE_UPLOAD]);
            $options[self::COS_CONTENT_MD5] = $content_md5;
        }
        if (!isset($options[self::COS_CONTENT_TYPE])) {
            $options[self::COS_CONTENT_TYPE] = $this->getMimeType($object, $file);
        }
        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = $object;
        $options[self::COS_CONTENT_LENGTH] = $file_size;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 拷贝一个在COS上已经存在的object成另外一个object
     *
     * @param string $fromBucket 源bucket名称
     * @param string $fromObject 源object名称
     * @param string $toBucket 目标bucket名称
     * @param string $toObject 目标object名称
     * @param array $options
     * @return null
     * @throws CosException
     */
    public function copyObject($fromBucket, $fromObject, $toBucket, $toObject, $options = NULL)
    {
        $this->precheckCommon($fromBucket, $fromObject, $options);
        $this->precheckCommon($toBucket, $toObject, $options);
        $options[self::COS_BUCKET] = $toBucket;
        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_OBJECT] = $toObject;
        if (isset($options[self::COS_HEADERS])) {
            $options[self::COS_HEADERS][self::COS_OBJECT_COPY_SOURCE] = '/' . $fromBucket . '/' . $fromObject;
        } else {
            $options[self::COS_HEADERS] = array(self::COS_OBJECT_COPY_SOURCE => '/' . $fromBucket . '/' . $fromObject);
        }
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 获取Object的Meta信息
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param string $options 具体参考SDK文档
     * @return array
     */
    public function getObjectMeta($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_HEAD;
        $options[self::COS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new HeaderResult($response);
        return $result->getData();
    }

    /**
     * 删除某个Object
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param array $options
     * @return null
     */
    public function deleteObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_DELETE;
        $options[self::COS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 删除同一个Bucket中的多个Object
     *
     * @param string $bucket bucket名称
     * @param array $objects object列表
     * @param array $options
     * @return ResponseCore
     * @throws null
     */
    public function deleteObjects($bucket, $objects, $options = null)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        if (!is_array($objects) || !$objects) {
            throw new CosException('objects must be array');
        }
        $options[self::COS_METHOD] = self::COS_HTTP_POST;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_SUB_RESOURCE] = 'delete';
        $options[self::COS_CONTENT_TYPE] = 'application/json';
        $quiet = 'false';
        if (isset($options['quiet'])) {
            if (is_bool($options['quiet'])) { //Boolean
                $quiet = $options['quiet'] ? 'true' : 'false';
            } elseif (is_string($options['quiet'])) { // string
                $quiet = ($options['quiet'] === 'true') ? 'true' : 'false';
            }
        }
        $jsonBody = CosUtil::createDeleteObjectsJsonBody($objects, $quiet);
        $options[self::COS_CONTENT] = $jsonBody;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 获得Object内容
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param array $options
     * @return string
     */
    public function getObject($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_GET;
        $options[self::COS_OBJECT] = $object;
        if (isset($options[self::COS_LAST_MODIFIED])) {
            $options[self::COS_HEADERS][self::COS_IF_MODIFIED_SINCE] = $options[self::COS_LAST_MODIFIED];
            unset($options[self::COS_LAST_MODIFIED]);
        }
        if (isset($options[self::COS_ETAG])) {
            $options[self::COS_HEADERS][self::COS_IF_NONE_MATCH] = $options[self::COS_ETAG];
            unset($options[self::COS_ETAG]);
        }
        if (isset($options[self::COS_RANGE])) {
            $range = $options[self::COS_RANGE];
            $options[self::COS_HEADERS][self::COS_RANGE] = "bytes=$range";
            unset($options[self::COS_RANGE]);
        }
        $response = $this->auth($options);
        $result = new BodyResult($response);
        return $result->getData();
    }

    /**
     * 检测Object是否存在
     * 通过获取Object的Meta信息来判断Object是否存在， 用户需要自行解析ResponseCore判断object是否存在
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param array $options
     * @return bool
     */
    public function doesObjectExist($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_METHOD] = self::COS_HTTP_HEAD;
        $options[self::COS_OBJECT] = $object;
        $response = $this->auth($options);
        $result = new ExistResult($response);
        return $result->getData();
    }

    /**
     * 获取分片大小，根据用户提供的part_size，重新计算一个更合理的partsize
     *
     * @param int $partSize
     * @return int
     */
    private function computePartSize($partSize)
    {
        $partSize = (integer)$partSize;
        if ($partSize <= self::COS_MIN_PART_SIZE) {
            $partSize = self::COS_MIN_PART_SIZE;
        } elseif ($partSize > self::COS_MAX_PART_SIZE) {
            $partSize = self::COS_MAX_PART_SIZE;
        }
        return $partSize;
    }

    /**
     * 计算文件可以分成多少个part，以及每个part的长度以及起始位置
     * 方法必须在 <upload_part()>中调用
     *
     * @param integer $file_size 文件大小
     * @param integer $partSize part大小,默认5M
     * @return array An array 包含 key-value 键值对. Key 为 `seekTo` 和 `length`.
     */
    public function generateMultiuploadParts($file_size, $partSize = 5242880)
    {
        $i = 0;
        $size_count = $file_size;
        $values = array();
        $partSize = $this->computePartSize($partSize);
        while ($size_count > 0) {
            $size_count -= $partSize;
            $values[] = array(
                self::COS_SEEK_TO => ($partSize * $i),
                self::COS_LENGTH => (($size_count > 0) ? $partSize : ($size_count + $partSize)),
            );
            $i++;
        }
        return $values;
    }

    /**
     * 初始化multi-part upload
     *
     * @param string $bucket Bucket名称
     * @param string $object Object名称
     * @param array $options Key-Value数组
     * @throws CosException
     * @return string 返回uploadid
     */
    public function initiateMultipartUpload($bucket, $object, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_METHOD] = self::COS_HTTP_POST;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = $object;
        $options[self::COS_SUB_RESOURCE] = 'uploads';
        $options[self::COS_CONTENT] = '';

        if (!isset($options[self::COS_CONTENT_TYPE])) {
            $options[self::COS_CONTENT_TYPE] = $this->getMimeType($object);
        }
        if (!isset($options[self::COS_HEADERS])) {
            $options[self::COS_HEADERS] = array();
        }
        $response = $this->auth($options);
        $result = new InitiateMultipartUploadResult($response);
        return $result->getData();
    }

    /**
     * 分片上传的块上传接口
     *
     * @param string $bucket Bucket名称
     * @param string $object Object名称
     * @param string $uploadId
     * @param array $options Key-Value数组
     * @return string eTag
     * @throws CosException
     */
    public function uploadPart($bucket, $object, $uploadId, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        $this->precheckParam($options, self::COS_FILE_UPLOAD, __FUNCTION__);
        $this->precheckParam($options, self::COS_PART_NUM, __FUNCTION__);

        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = $object;
        $options[self::COS_UPLOAD_ID] = $uploadId;

        if (isset($options[self::COS_LENGTH])) {
            $options[self::COS_CONTENT_LENGTH] = $options[self::COS_LENGTH];
        }
        $response = $this->auth($options);
        $result = new UploadPartResult($response);
        return $result->getData();
    }

    /**
     * 获取已成功上传的part
     *
     * @param string $bucket Bucket名称
     * @param string $object Object名称
     * @param string $uploadId uploadId
     * @param array $options Key-Value数组
     * @return ListPartsInfo
     * @throws CosException
     */
    public function listParts($bucket, $object, $uploadId, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_METHOD] = self::COS_HTTP_GET;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = $object;
        $options[self::COS_UPLOAD_ID] = $uploadId;
        $options[self::COS_QUERY_STRING] = array();
        foreach (array('max-parts', 'part-number-marker') as $param) {
            if (isset($options[$param])) {
                $options[self::COS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }
        $response = $this->auth($options);
        $result = new ListPartsResult($response);
        return $result->getData();
    }

    /**
     * 中止进行一半的分片上传操作
     *
     * @param string $bucket Bucket名称
     * @param string $object Object名称
     * @param string $uploadId uploadId
     * @param array $options Key-Value数组
     * @return null
     * @throws CosException
     */
    public function abortMultipartUpload($bucket, $object, $uploadId, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_METHOD] = self::COS_HTTP_DELETE;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = $object;
        $options[self::COS_UPLOAD_ID] = $uploadId;
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 在将所有数据Part都上传完成后，调用此接口完成本次分块上传
     *
     * @param string $bucket Bucket名称
     * @param string $object Object名称
     * @param string $uploadId uploadId
     * @param array $listParts array( array("PartNumber"=> int, "ETag"=>string))
     * @param array $options Key-Value数组
     * @throws CosException
     * @return null
     */
    public function completeMultipartUpload($bucket, $object, $uploadId, $listParts, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        $options[self::COS_METHOD] = self::COS_HTTP_POST;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = $object;
        $options[self::COS_UPLOAD_ID] = $uploadId;
        $options[self::COS_CONTENT_TYPE] = 'application/json';
        if (!is_array($listParts)) {
            throw new CosException("listParts must be array type");
        }
        $options[self::COS_CONTENT] = CosUtil::createCompleteMultipartUploadJsonBody($listParts);
        $response = $this->auth($options);
        $result = new PutSetDeleteResult($response);
        return $result->getData();
    }

    /**
     * 罗列出所有执行中的Multipart Upload事件，即已经被初始化的Multipart Upload但是未被
     * Complete或者Abort的Multipart Upload事件
     *
     * @param string $bucket bucket
     * @param array $options 关联数组
     * @throws CosException
     * @return ListMultipartUploadInfo
     */
    public function listMultipartUploads($bucket, $options = null)
    {
        $this->precheckCommon($bucket, NULL, $options, false);
        $options[self::COS_METHOD] = self::COS_HTTP_GET;
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = '/';
        $options[self::COS_SUB_RESOURCE] = 'uploads';

        foreach (array('delimiter', 'key-marker', 'max-uploads', 'prefix', 'upload-id-marker') as $param) {
            if (isset($options[$param])) {
                $options[self::COS_QUERY_STRING][$param] = $options[$param];
                unset($options[$param]);
            }
        }
        $query = isset($options[self::COS_QUERY_STRING]) ? $options[self::COS_QUERY_STRING] : array();
        $options[self::COS_QUERY_STRING] = array_merge(
            $query,
            array(self::COS_ENCODING_TYPE => self::COS_ENCODING_TYPE_URL)
        );

        $response = $this->auth($options);
        $result = new ListMultipartUploadResult($response);
        return $result->getData();
    }

    /**
     * 从一个已存在的Object中拷贝数据来上传一个Part
     *
     * @param string $fromBucket 源bucket名称
     * @param string $fromObject 源object名称
     * @param string $toBucket 目标bucket名称
     * @param string $toObject 目标object名称
     * @param int $partNumber 分块上传的块id
     * @param string $uploadId 初始化multipart upload返回的uploadid
     * @param array $options Key-Value数组
     * @return null
     * @throws CosException
     */
    public function uploadPartCopy($fromBucket, $fromObject, $toBucket, $toObject, $partNumber, $uploadId, $options = NULL)
    {
        $this->precheckCommon($fromBucket, $fromObject, $options);
        $this->precheckCommon($toBucket, $toObject, $options);

        //如果没有设置$options['isFullCopy']，则需要强制判断copy的起止位置
        $start_range = "0";
        if (isset($options['start'])) {
            $start_range = $options['start'];
        }
        $end_range = "";
        if (isset($options['end'])) {
            $end_range = $options['end'];
        }
        $options[self::COS_METHOD] = self::COS_HTTP_PUT;
        $options[self::COS_BUCKET] = $toBucket;
        $options[self::COS_OBJECT] = $toObject;
        $options[self::COS_PART_NUM] = $partNumber;
        $options[self::COS_UPLOAD_ID] = $uploadId;

        if (!isset($options[self::COS_HEADERS])) {
            $options[self::COS_HEADERS] = array();
        }

        $options[self::COS_HEADERS][self::COS_OBJECT_COPY_SOURCE] = '/' . $fromBucket . '/' . $fromObject;
        $options[self::COS_HEADERS][self::COS_OBJECT_COPY_SOURCE_RANGE] = "bytes=" . $start_range . "-" . $end_range;
        $response = $this->auth($options);
        $result = new UploadPartResult($response);
        return $result->getData();
    }

    /**
     * multipart上传统一封装，从初始化到完成multipart，以及出错后中止动作
     *
     * @param string $bucket bucket名称
     * @param string $object object名称
     * @param string $file 需要上传的本地文件的路径
     * @param array $options Key-Value数组
     * @return null
     * @throws CosException
     */
    public function multiuploadFile($bucket, $object, $file, $options = null)
    {
        $this->precheckCommon($bucket, $object, $options);
        if (isset($options[self::COS_LENGTH])) {
            $options[self::COS_CONTENT_LENGTH] = $options[self::COS_LENGTH];
            unset($options[self::COS_LENGTH]);
        }
        if (empty($file)) {
            throw new CosException("parameter invalid, file is empty");
        }
        $uploadFile = CosUtil::encodePath($file);
        if (!isset($options[self::COS_CONTENT_TYPE])) {
            $options[self::COS_CONTENT_TYPE] = $this->getMimeType($object, $uploadFile);
        }

        $upload_position = isset($options[self::COS_SEEK_TO]) ? (integer)$options[self::COS_SEEK_TO] : 0;

        if (isset($options[self::COS_CONTENT_LENGTH])) {
            $upload_file_size = (integer)$options[self::COS_CONTENT_LENGTH];
        } else {
            $upload_file_size = filesize($uploadFile);
            if ($upload_file_size !== false) {
                $upload_file_size -= $upload_position;
            }
        }

        if ($upload_position === false || !isset($upload_file_size) || $upload_file_size === false || $upload_file_size < 0) {
            throw new CosException('The size of `fileUpload` cannot be determined in ' . __FUNCTION__ . '().');
        }
        // 处理partSize
        if (isset($options[self::COS_PART_SIZE])) {
            $options[self::COS_PART_SIZE] = $this->computePartSize($options[self::COS_PART_SIZE]);
        } else {
            $options[self::COS_PART_SIZE] = self::COS_MID_PART_SIZE;
        }

        $is_check_md5 = $this->isCheckMD5($options);
        // 如果上传的文件小于partSize,则直接使用普通方式上传
        if ($upload_file_size < $options[self::COS_PART_SIZE] && !isset($options[self::COS_UPLOAD_ID])) {
            return $this->uploadFile($bucket, $object, $uploadFile, $options);
        }

        // 初始化multipart
        if (isset($options[self::COS_UPLOAD_ID])) {
            $uploadId = $options[self::COS_UPLOAD_ID];
        } else {
            // 初始化
            $uploadId = $this->initiateMultipartUpload($bucket, $object, $options);
        }
        // 获取的分片
        $pieces = $this->generateMultiuploadParts($upload_file_size, (integer)$options[self::COS_PART_SIZE]);
        $response_upload_part = array();
        foreach ($pieces as $i => $piece) {
            $from_pos = $upload_position + (integer)$piece[self::COS_SEEK_TO];
            $to_pos = (integer)$piece[self::COS_LENGTH] + $from_pos - 1;
            $up_options = array(
                self::COS_FILE_UPLOAD => $uploadFile,
                self::COS_PART_NUM => ($i + 1),
                self::COS_SEEK_TO => $from_pos,
                self::COS_LENGTH => $to_pos - $from_pos + 1,
                self::COS_CHECK_MD5 => $is_check_md5,
            );
            if ($is_check_md5) {
                $content_md5 = CosUtil::getMd5SumForFile($uploadFile, $from_pos, $to_pos);
                $up_options[self::COS_CONTENT_MD5] = $content_md5;
            }
            $response_upload_part[] = $this->uploadPart($bucket, $object, $uploadId, $up_options);
        }

        $uploadParts = array();
        foreach ($response_upload_part as $i => $etag) {
            $uploadParts[] = array(
                'PartNumber' => ($i + 1),
                'ETag' => $etag,
            );
        }
        return $this->completeMultipartUpload($bucket, $object, $uploadId, $uploadParts);
    }

    /**
     * 上传本地目录内的文件或者目录到指定bucket的指定prefix的object中
     *
     * @param string $bucket bucket名称
     * @param string $prefix 需要上传到的object的key前缀，可以理解成bucket中的子目录，结尾不能是'/'，接口中会补充'/'
     * @param string $localDirectory 需要上传的本地目录
     * @param string $exclude 需要排除的目录
     * @param bool $recursive 是否递归的上传localDirectory下的子目录内容
     * @param bool $checkMd5
     * @return array 返回两个列表 array("succeededList" => array("object"), "failedList" => array("object"=>"errorMessage"))
     * @throws CosException
     */
    public function uploadDir($bucket, $prefix, $localDirectory, $exclude = '.|..|.svn|.git', $recursive = false, $checkMd5 = true)
    {
        $retArray = array("succeededList" => array(), "failedList" => array());
        if (empty($bucket)) throw new CosException("parameter error, bucket is empty");
        if (!is_string($prefix)) throw new CosException("parameter error, prefix is not string");
        if (empty($localDirectory)) throw new CosException("parameter error, localDirectory is empty");
        $directory = $localDirectory;
        $directory = CosUtil::encodePath($directory);
        //判断是否目录
        if (!is_dir($directory)) {
            throw new CosException('parameter error: ' . $directory . ' is not a directory, please check it');
        }
        //read directory
        $file_list_array = CosUtil::readDir($directory, $exclude, $recursive);
        if (!$file_list_array) {
            throw new CosException($directory . ' is empty...');
        }
        foreach ($file_list_array as $k => $item) {
            if (is_dir($item['path'])) {
                continue;
            }
            $options = array(
                self::COS_PART_SIZE => self::COS_MIN_PART_SIZE,
                self::COS_CHECK_MD5 => $checkMd5,
            );
            $realObject = (!empty($prefix) ? $prefix . '/' : '') . $item['file'];

            try {
                $this->multiuploadFile($bucket, $realObject, $item['path'], $options);
                $retArray["succeededList"][] = $realObject;
            } catch (CosException $e) {
                $retArray["failedList"][$realObject] = $e->getMessage();
            }
        }
        return $retArray;
    }

    /**
     * 支持生成get和put签名, 用户可以生成一个具有一定有效期的
     * 签名过的url
     *
     * @param string $bucket
     * @param string $object
     * @param int $timeout
     * @param string $method
     * @param array $options Key-Value数组
     * @return string
     * @throws CosException
     */
    public function signUrl($bucket, $object, $timeout = 60, $method = self::COS_HTTP_GET, $options = NULL)
    {
        $this->precheckCommon($bucket, $object, $options);
        //method
        if (self::COS_HTTP_GET !== $method && self::COS_HTTP_PUT !== $method) {
            throw new CosException("method is invalid");
        }
        $options[self::COS_BUCKET] = $bucket;
        $options[self::COS_OBJECT] = $object;
        $options[self::COS_METHOD] = $method;
        if (!isset($options[self::COS_CONTENT_TYPE])) {
            $options[self::COS_CONTENT_TYPE] = '';
        }
        $timeout = time() + $timeout;
        $options[self::COS_PREAUTH] = $timeout;
        $options[self::COS_DATE] = $timeout;
        $this->setSignStsInUrl(true);
        return $this->auth($options);
    }

    /**
     * 检测options参数
     *
     * @param array $options
     * @throws CosException
     */
    private function precheckOptions(&$options)
    {
        CosUtil::validateOptions($options);
        if (!$options) {
            $options = array();
        }
    }

    /**
     * 校验bucket参数
     *
     * @param string $bucket
     * @param string $errMsg
     * @throws CosException
     */
    private function precheckBucket($bucket, $errMsg = 'bucket is not allowed empty')
    {
        CosUtil::throwCosExceptionWithMessageIfEmpty($bucket, $errMsg);
    }

    /**
     * 校验object参数
     *
     * @param string $object
     * @throws CosException
     */
    private function precheckObject($object)
    {
        CosUtil::throwCosExceptionWithMessageIfEmpty($object, "object name is empty");
    }

    /**
     * 校验bucket,options参数
     *
     * @param string $bucket
     * @param string $object
     * @param array $options
     * @param bool $isCheckObject
     */
    private function precheckCommon($bucket, $object, &$options, $isCheckObject = true)
    {
        if ($isCheckObject) {
            $this->precheckObject($object);
        }
        $this->precheckOptions($options);
        $this->precheckBucket($bucket);
    }

    /**
     * 参数校验
     *
     * @param array $options
     * @param string $param
     * @param string $funcName
     * @throws CosException
     */
    private function precheckParam($options, $param, $funcName)
    {
        if (!isset($options[$param])) {
            throw new CosException('The `' . $param . '` options is required in ' . $funcName . '().');
        }
    }

    /**
     * 检测md5
     *
     * @param array $options
     * @return bool|null
     */
    private function isCheckMD5($options)
    {
        return $this->getValue($options, self::COS_CHECK_MD5, false, true, true);
    }

    /**
     * 获取value
     *
     * @param array $options
     * @param string $key
     * @param string $default
     * @param bool $isCheckEmpty
     * @param bool $isCheckBool
     * @return bool|null
     */
    private function getValue($options, $key, $default = NULL, $isCheckEmpty = false, $isCheckBool = false)
    {
        $value = $default;
        if (isset($options[$key])) {
            if ($isCheckEmpty) {
                if (!empty($options[$key])) {
                    $value = $options[$key];
                }
            } else {
                $value = $options[$key];
            }
            unset($options[$key]);
        }
        if ($isCheckBool) {
            if ($value !== true && $value !== false) {
                $value = false;
            }
        }
        return $value;
    }

    /**
     * 获取mimetype类型
     *
     * @param string $object
     * @return string
     */
    private function getMimeType($object, $file = null)
    {
        if (!is_null($file)) {
            $type = MimeTypes::getMimetype($file);
            if (!is_null($type)) {
                return $type;
            }
        }

        $type = MimeTypes::getMimetype($object);
        if (!is_null($type)) {
            return $type;
        }

        return self::DEFAULT_CONTENT_TYPE;
    }

    /**
     * 验证并且执行请求，按照COS Api协议，执行操作
     *
     * @param array $options
     * @return ResponseCore
     * @throws CosException
     * @throws RequestCore_Exception
     */
    private function auth($options)
    {
        CosUtil::validateOptions($options);
        //验证bucket，list_bucket时不需要验证
        $this->authPrecheckBucket($options);
        //验证object
        $this->authPrecheckObject($options);
        //Object名称的编码必须是utf8
        $this->authPrecheckObjectEncoding($options);
        //验证ACL
        $this->authPrecheckAcl($options);
        // 获得当次请求使用的协议头，是https还是http
        $scheme = $this->useSSL ? 'https://' : 'http://';
        // 获得当次请求使用的hostname，如果是公共域名或者专有域名，bucket拼在前面构成三级域名
        $hostname = $this->generateHostname($options);
        $string_to_sign = '';
        $headers = $this->generateHeaders($options, $hostname);
        $signable_query_string_params = $this->generateSignableQueryStringParam($options);
        $signable_query_string = CosUtil::toQueryString($signable_query_string_params);
        $resource_uri = $this->generateResourceUri($options);
        //生成请求URL
        $conjunction = '?';
        $non_signable_resource = '';
        if (isset($options[self::COS_SUB_RESOURCE])) {
            $conjunction = '&';
        }
        if ($signable_query_string !== '') {
            $signable_query_string = $conjunction . $signable_query_string;
            $conjunction = '&';
        }
        $query_string = $this->generateQueryString($options);
        if ($query_string !== '') {
            $non_signable_resource .= $conjunction . $query_string;
            $conjunction = '&';
        }
        $this->requestUrl = $scheme . $hostname . $resource_uri . $signable_query_string . $non_signable_resource;

        //创建请求
        $request = new RequestCore($this->requestUrl);
        $request->set_useragent($this->generateUserAgent());
        // Streaming uploads
        if (isset($options[self::COS_FILE_UPLOAD])) {
            if (is_resource($options[self::COS_FILE_UPLOAD])) {
                $length = null;

                if (isset($options[self::COS_CONTENT_LENGTH])) {
                    $length = $options[self::COS_CONTENT_LENGTH];
                } elseif (isset($options[self::COS_SEEK_TO])) {
                    $stats = fstat($options[self::COS_FILE_UPLOAD]);
                    if ($stats && $stats[self::COS_SIZE] >= 0) {
                        $length = $stats[self::COS_SIZE] - (integer)$options[self::COS_SEEK_TO];
                    }
                }
                $request->set_read_stream($options[self::COS_FILE_UPLOAD], $length);
            } else {
                $request->set_read_file($options[self::COS_FILE_UPLOAD]);
                $length = $request->read_stream_size;
                if (isset($options[self::COS_CONTENT_LENGTH])) {
                    $length = $options[self::COS_CONTENT_LENGTH];
                } elseif (isset($options[self::COS_SEEK_TO]) && isset($length)) {
                    $length -= (integer)$options[self::COS_SEEK_TO];
                }
                $request->set_read_stream_size($length);
            }
        }
        if (isset($options[self::COS_SEEK_TO])) {
            $request->set_seek_position((integer)$options[self::COS_SEEK_TO]);
        }
        if (isset($options[self::COS_FILE_DOWNLOAD])) {
            if (is_resource($options[self::COS_FILE_DOWNLOAD])) {
                $request->set_write_stream($options[self::COS_FILE_DOWNLOAD]);
            } else {
                $request->set_write_file($options[self::COS_FILE_DOWNLOAD]);
            }
        }

        if (isset($options[self::COS_METHOD])) {
            $request->set_method($options[self::COS_METHOD]);
            $string_to_sign .= $options[self::COS_METHOD] . "\n";
        }

        if (isset($options[self::COS_CONTENT])) {
            $request->set_body($options[self::COS_CONTENT]);
            if ($headers[self::COS_CONTENT_TYPE] === 'application/x-www-form-urlencoded') {
                $headers[self::COS_CONTENT_TYPE] = 'application/octet-stream';
            }

            $headers[self::COS_CONTENT_LENGTH] = strlen($options[self::COS_CONTENT]);
            $headers[self::COS_CONTENT_MD5] = md5($options[self::COS_CONTENT]);
        }

        uksort($headers, 'strnatcasecmp');
        foreach ($headers as $header_key => $header_value) {
            $header_value = str_replace(array("\r", "\n"), '', $header_value);
            if ($header_value !== '') {
                $request->add_header($header_key, $header_value);
            }
            if (
                strtolower($header_key) === 'content-md5' ||
                strtolower($header_key) === 'content-type' ||
                strtolower($header_key) === 'date' ||
                (isset($options['self::COS_PREAUTH']) && (integer)$options['self::COS_PREAUTH'] > 0)
            ) {
                $string_to_sign .= $header_value . "\n";
            } elseif (substr(strtolower($header_key), 0, 6) === self::COS_DEFAULT_PREFIX) {
                $string_to_sign .= strtolower($header_key) . ':' . $header_value . "\n";
            }
        }
        // 生成 signable_resource
        $signable_resource = $this->generateSignableResource($options);
        //$string_to_sign .= rawurldecode($signable_resource) . urldecode($signable_query_string);
        $string_to_sign .= $signable_resource . $signable_query_string;
        $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $this->accessKeySecret, true));
        $request->add_header('Authorization', 'COS ' . $this->accessKeyId . ':' . $signature);

        if (isset($options[self::COS_PREAUTH]) && (integer)$options[self::COS_PREAUTH] > 0) {
            $signed_url = $this->requestUrl . $conjunction . self::COS_URL_ACCESS_KEY_ID . '=' . rawurlencode($this->accessKeyId) . '&' . self::COS_URL_EXPIRES . '=' . $options[self::COS_PREAUTH] . '&' . self::COS_URL_SIGNATURE . '=' . rawurlencode($signature);
            return $signed_url;
        } elseif (isset($options[self::COS_PREAUTH])) {
            return $this->requestUrl;
        }

        if ($this->timeout !== 0) {
            $request->timeout = $this->timeout;
        }
        if ($this->connectTimeout !== 0) {
            $request->connect_timeout = $this->connectTimeout;
        }

        try {
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            throw(new CosException('RequestCoreException: ' . $e->getMessage()));
        }
        $response_header = $request->get_response_header();
        $response_header['cos-request-url'] = $this->requestUrl;
        $response_header['cos-redirects'] = $this->redirects;
        $response_header['cos-stringtosign'] = $string_to_sign;
        $response_header['cos-requestheaders'] = $request->request_headers;

        $data = new ResponseCore($response_header, $request->get_response_body(), $request->get_response_code());
        //retry if COS Internal Error
        if ((integer)$request->get_response_code() === 500) {
            if ($this->redirects <= $this->maxRetries) {
                //设置休眠
                $delay = (integer)(pow(4, $this->redirects) * 100000);
                usleep($delay);
                $this->redirects++;
                $data = $this->auth($options);
            }
        }

        $this->redirects = 0;
        return $data;
    }

    /**
     * 设置最大尝试次数
     *
     * @param int $maxRetries
     * @return void
     */
    public function setMaxTries($maxRetries = 3)
    {
        $this->maxRetries = $maxRetries;
    }

    /**
     * 获取最大尝试次数
     *
     * @return int
     */
    public function getMaxRetries()
    {
        return $this->maxRetries;
    }

    /**
     * 打开sts enable标志，使用户构造函数中传入的$sts生效
     *
     * @param boolean $enable
     */
    public function setSignStsInUrl($enable)
    {
        $this->enableStsInUrl = $enable;
    }

    /**
     * @return boolean
     */
    public function isUseSSL()
    {
        return $this->useSSL;
    }

    /**
     * @param boolean $useSSL
     */
    public function setUseSSL($useSSL)
    {
        $this->useSSL = $useSSL;
    }

    /**
     * 检查bucket名称格式是否正确，如果非法抛出异常
     *
     * @param $options
     * @throws CosException
     */
    private function authPrecheckBucket($options)
    {
        if (!(('/' == $options[self::COS_OBJECT]) && ('' == $options[self::COS_BUCKET]) && ('GET' == $options[self::COS_METHOD])) && !CosUtil::validateBucket($options[self::COS_BUCKET])) {
            throw new CosException('"' . $options[self::COS_BUCKET] . '"' . 'bucket name is invalid');
        }
    }

    /**
     *
     * 检查object名称格式是否正确，如果非法抛出异常
     *
     * @param $options
     * @throws CosException
     */
    private function authPrecheckObject($options)
    {
        if (isset($options[self::COS_OBJECT]) && $options[self::COS_OBJECT] === '/') {
            return;
        }

        if (isset($options[self::COS_OBJECT]) && !CosUtil::validateObject($options[self::COS_OBJECT])) {
            throw new CosException('"' . $options[self::COS_OBJECT] . '"' . ' object name is invalid');
        }
    }

    /**
     * 检查object的编码，如果是gbk或者gb2312则尝试将其转化为utf8编码
     *
     * @param mixed $options 参数
     */
    private function authPrecheckObjectEncoding(&$options)
    {
        $tmp_object = $options[self::COS_OBJECT];
        try {
            if (CosUtil::isGb2312($options[self::COS_OBJECT])) {
                $options[self::COS_OBJECT] = iconv('GB2312', "UTF-8//IGNORE", $options[self::COS_OBJECT]);
            } elseif (CosUtil::checkChar($options[self::COS_OBJECT], true)) {
                $options[self::COS_OBJECT] = iconv('GBK', "UTF-8//IGNORE", $options[self::COS_OBJECT]);
            }
        } catch (\Exception $e) {
            try {
                $tmp_object = iconv(mb_detect_encoding($tmp_object), "UTF-8", $tmp_object);
            } catch (\Exception $e) {
            }
        }
        $options[self::COS_OBJECT] = $tmp_object;
    }

    /**
     * 检查ACL是否是预定义中三种之一，如果不是抛出异常
     *
     * @param $options
     * @throws CosException
     */
    private function authPrecheckAcl($options)
    {
        if (isset($options[self::COS_HEADERS][self::COS_ACL]) && !empty($options[self::COS_HEADERS][self::COS_ACL])) {
            if (!in_array(strtolower($options[self::COS_HEADERS][self::COS_ACL]), self::$COS_ACL_TYPES)) {
                throw new CosException($options[self::COS_HEADERS][self::COS_ACL] . ':' . 'acl is invalid(private,public-read)');
            }
        }
    }

    /**
     * 获得档次请求使用的域名
     *
     * @param $options
     * @return string 剥掉协议头的域名
     */
    private function generateHostname($options)
    {
        if ($this->hostType === self::COS_HOST_TYPE_IP) {
            $hostname = $this->hostname;
        } elseif ($this->hostType === self::COS_HOST_TYPE_CNAME) {
            $hostname = $this->hostname;
        } else {
            // 专有域或者官网endpoint
            $hostname = ($options[self::COS_BUCKET] == '') ? $this->hostname : ($options[self::COS_BUCKET] . '.') . $this->hostname;
        }
        return $hostname;
    }

    /**
     * 获得当次请求的资源定位字段
     *
     * @param $options
     * @return string 资源定位字段
     */
    private function generateResourceUri($options)
    {
        $resource_uri = "";

        // resource_uri + bucket
        if (isset($options[self::COS_BUCKET]) && '' !== $options[self::COS_BUCKET]) {
            if ($this->hostType === self::COS_HOST_TYPE_IP) {
                $resource_uri = '/' . $options[self::COS_BUCKET];
            }
        }

        // resource_uri + object
        if (isset($options[self::COS_OBJECT]) && '/' !== $options[self::COS_OBJECT]) {
            $resource_uri .= '/' . str_replace(array('%2F', '%25'), array('/', '%'), rawurlencode($options[self::COS_OBJECT]));
        }

        // resource_uri + sub_resource
        $conjunction = '?';
        if (isset($options[self::COS_SUB_RESOURCE])) {
            $resource_uri .= $conjunction . $options[self::COS_SUB_RESOURCE];
        }
        return $resource_uri;
    }

    /**
     * 生成signalbe_query_string_param, array类型
     *
     * @param array $options
     * @return array
     */
    private function generateSignableQueryStringParam($options)
    {
        $signableQueryStringParams = array();
        $signableList = array(
            self::COS_PART_NUM,
            'response-content-type',
            'response-content-language',
            'response-cache-control',
            'response-content-encoding',
            'response-expires',
            'response-content-disposition',
            self::COS_UPLOAD_ID,
            self::COS_CNAME_COMP
        );

        foreach ($signableList as $item) {
            if (isset($options[$item])) {
                $signableQueryStringParams[$item] = $options[$item];
            }
        }

        if ($this->enableStsInUrl && (!is_null($this->securityToken))) {
            $signableQueryStringParams["security-token"] = $this->securityToken;
        }

        return $signableQueryStringParams;
    }

    /**
     *  生成用于签名resource段
     *
     * @param mixed $options
     * @return string
     */
    private function generateSignableResource($options)
    {
        $signableResource = "";
        $signableResource .= '/';
        if (isset($options[self::COS_BUCKET]) && '' !== $options[self::COS_BUCKET]) {
            $signableResource .= $options[self::COS_BUCKET];
            // 如果操作没有Object操作的话，这里最后是否有斜线有个trick，ip的域名下，不需要加'/'， 否则需要加'/'
            if ($options[self::COS_OBJECT] == '/') {
                if ($this->hostType !== self::COS_HOST_TYPE_IP) {
                    $signableResource .= "/";
                }
            }
        }
        //signable_resource + object
        if (isset($options[self::COS_OBJECT]) && '/' !== $options[self::COS_OBJECT]) {
            // $signableResource .= '/' . str_replace(array('%2F', '%25'), array('/', '%'), rawurlencode($options[self::COS_OBJECT]));
            $signableResource .= '/' . rawurlencode($options[self::COS_OBJECT]);

        }
        if (isset($options[self::COS_SUB_RESOURCE])) {
            $signableResource .= '?' . $options[self::COS_SUB_RESOURCE];
        }
        return $signableResource;
    }

    /**
     * 生成query_string
     *
     * @param mixed $options
     * @return string
     */
    private function generateQueryString($options)
    {
        //请求参数
        $queryStringParams = array();
        if (isset($options[self::COS_QUERY_STRING])) {
            $queryStringParams = array_merge($queryStringParams, $options[self::COS_QUERY_STRING]);
        }
        return CosUtil::toQueryString($queryStringParams);
    }

    /**
     * 初始化headers
     *
     * @param mixed $options
     * @param string $hostname hostname
     * @return array
     */
    private function generateHeaders($options, $hostname)
    {
        $headers = array(
            self::COS_CONTENT_MD5 => '',
            self::COS_CONTENT_TYPE => isset($options[self::COS_CONTENT_TYPE]) ? $options[self::COS_CONTENT_TYPE] : self::DEFAULT_CONTENT_TYPE,
            self::COS_DATE => isset($options[self::COS_DATE]) ? $options[self::COS_DATE] : gmdate('D, d M Y H:i:s \G\M\T'),
            self::COS_HOST => $hostname,
        );
        if (isset($options[self::COS_CONTENT_MD5])) {
            $headers[self::COS_CONTENT_MD5] = $options[self::COS_CONTENT_MD5];
        }
        //添加stsSecurityToken
        if ((!is_null($this->securityToken)) && (!$this->enableStsInUrl)) {
            $headers[self::COS_SECURITY_TOKEN] = $this->securityToken;
        }
        //合并HTTP headers
        if (isset($options[self::COS_HEADERS])) {
            $headers = array_merge($headers, $options[self::COS_HEADERS]);
        }
        return $headers;
    }

    /**
     * 生成请求用的UserAgent
     *
     * @return string
     */
    private function generateUserAgent()
    {
        return self::COS_NAME . "/" . self::COS_VERSION . " (" . php_uname('s') . "/" . php_uname('r') . "/" . php_uname('m') . ";" . PHP_VERSION . ")";
    }

    /**
     * 检查endpoint的种类
     * 如有有协议头，剥去协议头
     *
     * @param string $endpoint
     * @param boolean $isCName
     * @return string 剥掉协议头的域名
     */
    private function checkEndpoint($endpoint, $isCName)
    {
        $ret_endpoint = null;
        if (strpos($endpoint, 'http://') === 0) {
            $ret_endpoint = substr($endpoint, strlen('http://'));
        } elseif (strpos($endpoint, 'https://') === 0) {
            $ret_endpoint = substr($endpoint, strlen('https://'));
            $this->useSSL = true;
        } else {
            $ret_endpoint = $endpoint;
        }

        if ($isCName) {
            $this->hostType = self::COS_HOST_TYPE_CNAME;
        } elseif (CosUtil::isIPFormat($ret_endpoint)) {
            $this->hostType = self::COS_HOST_TYPE_IP;
        } else {
            $this->hostType = self::COS_HOST_TYPE_NORMAL;
        }
        return $ret_endpoint;
    }

    /**
     * 用来检查sdk所以来的扩展是否打开
     *
     * @throws CosException
     */
    public static function checkEnv()
    {
        if (function_exists('get_loaded_extensions')) {
            //检测curl扩展
            $enabled_extension = array("curl");
            $extensions = get_loaded_extensions();
            if ($extensions) {
                foreach ($enabled_extension as $item) {
                    if (!in_array($item, $extensions)) {
                        throw new CosException("Extension {" . $item . "} is not installed or not enabled, please check your php env.");
                    }
                }
            } else {
                throw new CosException("function get_loaded_extensions not found.");
            }
        } else {
            throw new CosException('Function get_loaded_extensions has been disabled, please check php config.');
        }
    }

    /**
     * 设置http库的请求超时时间，单位秒
     *
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * 设置http库的连接超时时间，单位秒
     *
     * @param int $connectTimeout
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    // 生命周期相关常量
    const COS_LIFECYCLE_EXPIRATION = "Expiration";
    const COS_LIFECYCLE_TIMING_DAYS = "Days";
    const COS_LIFECYCLE_TIMING_DATE = "Date";
    //COS 内部常量
    const COS_BUCKET = 'bucket';
    const COS_OBJECT = 'object';
    const COS_HEADERS = CosUtil::COS_HEADERS;
    const COS_METHOD = 'method';
    const COS_QUERY = 'query';
    const COS_BASENAME = 'basename';
    const COS_MAX_KEYS = 'max-keys';
    const COS_UPLOAD_ID = 'uploadId';
    const COS_PART_NUM = 'partNumber';
    const COS_CNAME_COMP = 'comp';
    const COS_MAX_KEYS_VALUE = 100;
    const COS_MAX_OBJECT_GROUP_VALUE = CosUtil::COS_MAX_OBJECT_GROUP_VALUE;
    const COS_MAX_PART_SIZE = CosUtil::COS_MAX_PART_SIZE;
    const COS_MID_PART_SIZE = CosUtil::COS_MID_PART_SIZE;
    const COS_MIN_PART_SIZE = CosUtil::COS_MIN_PART_SIZE;
    const COS_FILE_SLICE_SIZE = 8192;
    const COS_PREFIX = 'prefix';
    const COS_DELIMITER = 'delimiter';
    const COS_MARKER = 'marker';
    const COS_CONTENT_MD5 = 'Content-Md5';
    const COS_SELF_CONTENT_MD5 = 'x-cos-meta-md5';
    const COS_CONTENT_TYPE = 'Content-Type';
    const COS_CONTENT_LENGTH = 'Content-Length';
    const COS_IF_MODIFIED_SINCE = 'If-Modified-Since';
    const COS_IF_UNMODIFIED_SINCE = 'If-Unmodified-Since';
    const COS_IF_MATCH = 'If-Match';
    const COS_IF_NONE_MATCH = 'If-None-Match';
    const COS_CACHE_CONTROL = 'Cache-Control';
    const COS_EXPIRES = 'Expires';
    const COS_PREAUTH = 'preauth';
    const COS_CONTENT_COING = 'Content-Coding';
    const COS_CONTENT_DISPOSTION = 'Content-Disposition';
    const COS_RANGE = 'range';
    const COS_ETAG = 'etag';
    const COS_LAST_MODIFIED = 'lastmodified';
    const OS_CONTENT_RANGE = 'Content-Range';
    const COS_CONTENT = CosUtil::COS_CONTENT;
    const COS_BODY = 'body';
    const COS_LENGTH = CosUtil::COS_LENGTH;
    const COS_HOST = 'Host';
    const COS_DATE = 'Date';
    const COS_AUTHORIZATION = 'Authorization';
    const COS_FILE_DOWNLOAD = 'fileDownload';
    const COS_FILE_UPLOAD = 'fileUpload';
    const COS_PART_SIZE = 'partSize';
    const COS_SEEK_TO = 'seekTo';
    const COS_SIZE = 'size';
    const COS_QUERY_STRING = 'query_string';
    const COS_SUB_RESOURCE = 'sub_resource';
    const COS_DEFAULT_PREFIX = 'x-cos-';
    const COS_CHECK_MD5 = 'checkmd5';
    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    //私有URL变量
    const COS_URL_ACCESS_KEY_ID = 'COSAccessKeyId';
    const COS_URL_EXPIRES = 'Expires';
    const COS_URL_SIGNATURE = 'Signature';
    //HTTP方法
    const COS_HTTP_GET = 'GET';
    const COS_HTTP_PUT = 'PUT';
    const COS_HTTP_HEAD = 'HEAD';
    const COS_HTTP_POST = 'POST';
    const COS_HTTP_DELETE = 'DELETE';
    const COS_HTTP_OPTIONS = 'OPTIONS';
    //其他常量
    const COS_ACL = 'x-cos-acl';
    const COS_OBJECT_ACL = 'x-cos-object-acl';
    const COS_OBJECT_GROUP = 'x-cos-file-group';
    const COS_MULTI_PART = 'uploads';
    const COS_MULTI_DELETE = 'delete';
    const COS_OBJECT_COPY_SOURCE = 'x-cos-copy-source';
    const COS_OBJECT_COPY_SOURCE_RANGE = "x-cos-copy-source-range";
    //支持STS SecurityToken
    const COS_SECURITY_TOKEN = "x-cos-security-token";
    const COS_ACL_TYPE_PRIVATE = 'private';
    const COS_ACL_TYPE_PUBLIC_READ = 'public-read';
    const COS_ENCODING_TYPE = "encoding-type";
    const COS_ENCODING_TYPE_URL = "url";

    // 域名类型
    const COS_HOST_TYPE_NORMAL = "normal";
    const COS_HOST_TYPE_IP = "ip";  //http://1.1.1.1/bucket/object
    const COS_HOST_TYPE_SPECIAL = 'special'; //http://bucket.guizhou.gov/object
    const COS_HOST_TYPE_CNAME = "cname";  //http://mydomain.com/object
    //COS ACL数组
    static $COS_ACL_TYPES = array(
        self::COS_ACL_TYPE_PRIVATE,
        self::COS_ACL_TYPE_PUBLIC_READ,
        // self::COS_ACL_TYPE_PUBLIC_READ_WRITE
    );
    // CosClient版本信息
    const COS_NAME = "cos-sdk-php";
    const COS_VERSION = "2.0.5";
    const COS_BUILD = "20160126";
    const COS_AUTHOR = "";
    const COS_OPTIONS_ORIGIN = 'Origin';
    const COS_OPTIONS_REQUEST_METHOD = 'Access-Control-Request-Method';
    const COS_OPTIONS_REQUEST_HEADERS = 'Access-Control-Request-Headers';

    //是否使用ssl
    private $useSSL = false;
    private $maxRetries = 3;
    private $redirects = 0;

    // 用户提供的域名类型，有四种 COS_HOST_TYPE_NORMAL, COS_HOST_TYPE_IP, COS_HOST_TYPE_SPECIAL, COS_HOST_TYPE_CNAME
    private $hostType = self::COS_HOST_TYPE_NORMAL;
    private $requestUrl;
    private $accessKeyId;
    private $accessKeySecret;
    private $hostname;
    private $securityToken;
    private $enableStsInUrl = false;
    private $timeout = 0;
    private $connectTimeout = 0;
}
