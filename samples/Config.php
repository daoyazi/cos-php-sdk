<?php

/**
 * Class Config
 *
 * 执行Sample示例所需要的配置，用户在这里配置好Endpoint，AccessId， AccessKey和Sample示例操作的
 * bucket后，便可以直接运行RunAll.php, 运行所有的samples
 */
final class Config
{
    const COS_ACCESS_ID = 'dcbf4036e50a4135aeab604f729a8115';
    const COS_ACCESS_KEY = 'b36e5f786b794e7ca4d5026c896976c9';
    const COS_ENDPOINT = 'cos-beta.chinac.com';
    const COS_TEST_BUCKET = 'cos-php-sdk-funtion-test';
}
