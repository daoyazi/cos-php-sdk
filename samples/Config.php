<?php

/**
 * Class Config
 *
 * 执行Sample示例所需要的配置，用户在这里配置好Endpoint，AccessId， AccessKey和Sample示例操作的
 * bucket后，便可以直接运行RunAll.php, 运行所有的samples
 */

final class Config
{
    public function __construct()
    {
        $this->COS_ACCESS_ID = $_ENV["COS_ACCESS_KEY_ID"];
        $this->COS_ACCESS_KEY = $_ENV["COS_ACCESS_KEY_SECRET"];
        $this->COS_ENDPOINT = $_ENV["COS_ENDPOINT"];
        $this->COS_TEST_BUCKET = $_ENV["COS_TEST_BUCKET"];
    }

    public $COS_ACCESS_ID;
    public $COS_ACCESS_KEY;
    public $COS_ENDPOINT;
    public $COS_TEST_BUCKET;
}
