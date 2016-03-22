<?php

error_reporting(E_ALL | E_NOTICE);

$_ENV["COS_ACCESS_KEY_ID"]=$argv[1];
$_ENV["COS_ACCESS_KEY_SECRET"]=$argv[2];
$_ENV["COS_ENDPOINT"]=$argv[3];
$_ENV["COS_TEST_BUCKET"]=$argv[4];

require_once __DIR__ . '/Bucket.php';
require_once __DIR__ . '/BucketWebsite.php';
require_once __DIR__ . '/MultipartUpload.php';
require_once __DIR__ . '/Signature.php';

# In the listAllObjects method, we will delete the bucket
require_once __DIR__ . '/Object.php';

