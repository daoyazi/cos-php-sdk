<?php

namespace COS\Result;


/**
 * Class HeaderResult
 * @package COS\Result
 */
class HeaderResult extends Result
{
    /**
     * 把返回的ResponseCore中的header作为返回数据
     *
     * @return array
     */
    protected function parseDataFromResponse()
    {
        return empty($this->rawResponse->header) ? array() : $this->rawResponse->header;
    }

}
