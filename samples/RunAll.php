<?php

error_reporting(E_ALL | E_NOTICE);

require_once __DIR__ . '/Bucket.php';
require_once __DIR__ . '/BucketWebsite.php';
require_once __DIR__ . '/MultipartUpload.php';
require_once __DIR__ . '/Signature.php';

# In the listAllObjects method, we will delete the bucket
require_once __DIR__ . '/Object.php';

