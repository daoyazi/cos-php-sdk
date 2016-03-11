<?php

namespace COS\Model;

/**
 * Interface JsonConfig
 * @package COS\Model
 */
interface JsonConfig
{

    /**
     * 接口定义，实现此接口的类都需要实现从json数据解析的函数
     *
     * @param string $strJson
     * @return null
     */
    public function parseFromJson($strJson);

    /**
     * 接口定义，实现此接口的类，都需要实现把子类序列化成json字符串的接口
     *
     * @return string
     */
    public function serializeToJson();

}
