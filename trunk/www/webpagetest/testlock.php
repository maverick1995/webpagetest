<?php
require_once('common_lib.inc');
set_time_limit(30);

$start = time();
$lock = Lock("test lock");
if ($lock) {
  $elapsed = time() - $start;
  echo "Lock acquired in $elapsed seconds";
//  sleep(10);
//  Unlock($lock);
} else {
  echo "Lock failed";
}
?>
