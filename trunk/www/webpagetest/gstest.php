<?php
require_once('./lib/S3.php');
gsUpload('./gstest.php', 'httparchive', 'desktop-test/test.txt');

function gsUpload($file, $bucket, $remoteFile) {
  $ret = false;
  $key = 'GOOGT4X7CFTWS2VWN2HT';
  $secret = 'SEWZTyKZH6dNbjbT2CHg5Q5pUh5Y5+iinj0yBFB4';
  $server = 'storage.googleapis.com';
  $s3 = new S3($key, $secret, false, $server);
  $metaHeaders = array();
  $requestHeaders = array();
  if ($s3->putObject($s3->inputFile($file, false), $bucket, $remoteFile, S3::ACL_PUBLIC_READ, $metaHeaders, $requestHeaders)) {
    $ret = true;
  }
  return $ret;
}
?>
