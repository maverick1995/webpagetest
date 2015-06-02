<?php
header ("Content-type: application/json; charset=utf-8");
$url = "http://www.webpagetest.org/jsonResult.php?pretty=1&test=131125_YN_BEE";
echo file_get_contents($url);
?>
