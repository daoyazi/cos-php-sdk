<?php

namespace COS\Tests;

use COS\Core\MimeTypes;

class MimeTypesTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMimeType()
    {
        $this->assertEquals('application/json', MimeTypes::getMimetype('file.json'));
    }
}
