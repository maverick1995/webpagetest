<?php
chdir('..');
include('common.inc');
require_once('./lib/S3.php');
require_once('./lib/beanstalkd/pheanstalk_init.php');
require_once('./testStatus.inc');
require_once('har.inc.php');
set_time_limit(0);
error_reporting(E_ERROR | E_PARSE);

$tempDir = __DIR__ . '/har_tmp';
$statusFile = null;
$tests = null;
$exit = 1;
$beanstalkd = GetSetting('beanstalkd');
$pheanstalk = null;
if ($beanstalkd) {
  $db = mysql_connect('localhost', 'root', 'HTTP@rchive');
  if ( mysql_select_db('httparchive') ) {
    if (isset($argv[1])) {
      $crawl = $argv[1];
      $log = "$tempDir/$crawl.log";
      if ($name = GetCrawlName($crawl)) {
        if (LoadTests($crawl)) {
          $total = PendingTestCount($crawl);
          if ($total !== 0) {
            echo "Processing $total tests...";
            
            // spawn a bunch of processes to do the actual work
            if (isset($pheanstalk))
              unset($pheanstalk);
            $children = array();
            $pid = 0;
            for ($i = 0; $i < 20; $i++) {
              $pid = pcntl_fork();
              if ($pid) {
                $children[$pid] = $pid;
              } else {
                ProcessTests($crawl);
                exit(0);
              }
            }
            
            // wait for the child processes to finish and the queue to empty
            do {
              $pending = PendingTestCount($crawl);
              $done = $total - $pending;
              $percent = number_format(($done * 100.0) / floatval($total), 2);
              echo str_pad("\r$percent% complete ($done of $total)...", 80);
              if ($pending)
                sleep(5);
            } while ($pending !== 0);
            
            echo str_pad("\rMarking crawl as done.", 80) . "\n";
            MarkDone();
            $exit = 0;
            echo "Done.\n";
          } else {
            echo "$crawl has already been processed\n";
          }
        } else {
          echo "$crawl had no available tests\n";
        }
      } else {
        echo "$crawl is not a valid crawl ID (or is still running).  Available crawls are:\n\n";
        ShowAvailableCrawls();
      }
    } else {
        echo "Usage: php harupload.php <crawlid>\nThe available crawl ID's are:\n\n";
        ShowAvailableCrawls();
    }
    mysql_close($db);
  } else {
    echo "Unable to connect to database";
  }
} else {
  echo "beanstalkd not configured";
}
exit($exit);

function GetCrawlName($id) {
  $name = null;
  $crawls = GetCrawls();
  if (isset($crawls[$id]))
    $name = $crawls[$id];
  return $name;
}

function ShowAvailableCrawls() {
  $crawls = GetCrawls();
  foreach($crawls as $id => $name)
    echo "$id: $name\n";
}

function GetCrawls() {
  global $db;
  $crawls = array();
  $result = mysql_query("SELECT crawlid,label,location FROM crawls WHERE finishedDateTime IS NOT null;", $db);
  if ($result !== false) {
    while ($row = mysql_fetch_assoc($result)) {
      $type = $row['location'];
      if ($type == 'IE8')
        $type = 'desktop';
      elseif ($type == 'iphone4')
        $type = 'mobile';
      $id = intval($row['crawlid']);
      $name = str_replace(' ', '_', $type) . '-' . str_replace(' ', '_', $row['label']);
      $crawls[$id] = $name;
    }
  }
  return $crawls;
}

function LoadTests($crawl) {
  global $tempDir;
  global $tests;
  global $statusFile;
  global $db;
  global $beanstalkd;
  global $pheanstalk;
  if (!is_dir($tempDir))
    mkdir($tempDir, 0777, true);

  if (PendingTestCount($crawl) === 0) {
    $tube = 'har.' . $crawl;
    echo "Building list of test IDs in tube '$tube'...\n";
    $result = mysql_query("SELECT wptid FROM pagesdev WHERE crawlid=$crawl;", $db);
    $count = 0;
    if ($result !== false) {
      while ($row = mysql_fetch_assoc($result)) {
        try {
          $pheanstalk->putInTube($tube, $row['wptid'], 1024, 0, 600);
          $count++;
        } catch(Exception $e) {
        }
      }
    }
    $result = mysql_query("SELECT wptid FROM pagesmobile WHERE crawlid=$crawl;", $db);
    if ($result !== false) {
      while ($row = mysql_fetch_assoc($result)) {
        try {
          $pheanstalk->putInTube($tube, $row['wptid'], 1024, 0, 600);
          $count++;
        } catch(Exception $e) {
        }
      }
    }
    echo "Test ID list complete ($count tests)...\n";
  }
  return PendingTestCount($crawl) !== 0;
}

function PendingTestCount($crawl) {
  global $beanstalkd;
  global $pheanstalk;
  $count = false;
  do {
    if (!isset($pheanstalk))
      $pheanstalk = new Pheanstalk_Pheanstalk($beanstalkd);
    $tube = 'har.' . $crawl;
    try {
      $stats = $pheanstalk->statsTube($tube);
      $count = intval($stats['current-jobs-ready']) + intval($stats['current-jobs-reserved']);
    } catch(Exception $e) {
      if ($e->getMessage() == 'Server reported NOT_FOUND') {
        $count = 0;
      } else {
        echo "statsTube exception: " . $e->getCode() . " - " . $e->getMessage() . "\n";
        unset($pheanstalk);
        sleep(1);
        $pheanstalk = new Pheanstalk_Pheanstalk($beanstalkd);
      }
    }
    if ($count === false)
      sleep(1);
  } while ($count === false);
  return $count;
}

function ProcessTests($crawl) {
  global $beanstalkd;
  $tube = 'har.' . $crawl;
  $pheanstalk = new Pheanstalk_Pheanstalk($beanstalkd);
  do {
    $got_test = true;
    try {
      $job = $pheanstalk->reserveFromTube($tube, 0);
      if ($job !== false) {
        $id = $job->getData();
        if (ProcessTest($id)) {
          $pheanstalk->delete($job);
        } else {
          $pheanstalk->release($job);
        }
      } else {
        $got_test = false;
      }
    } catch(Exception $e) {
      if ($e->getMessage() == 'Server reported NOT_FOUND') {
        $got_test = false;
      } else {
        unset($pheanstalk);
        sleep(1);
        $pheanstalk = new Pheanstalk_Pheanstalk($beanstalkd);
      }
    }
  } while($got_test);
  unset($pheanstalk);
}

function ProcessTest($id) {
  global $tempDir;
  global $name;
  global $count;
  global $total;
  $ok = false;
  $testPath = './' . GetTestPath($id);
  $restored = false;
  if (!is_dir($testPath)) {
    // try restoring the test several times in case there are network issues
    $attempt = 0;
    do {
      $attempt++;
      har_log("{$id} - restoring test ($attempt)");
      RestoreTest($id);
      if (is_dir($testPath)) {
        $restored = true;
      } else {
        sleep(1);
      }
    } while (!$restored && $attempt < 120);
  }
  if (is_dir($testPath)) {
    har_log("{$id} - generating HAR");
    $har = GenerateHAR($id, $testPath, ['bodies' => 1, 'run' => 'median', 'cached' => 0]);
    if (isset($har) && strlen($har)) {
      gz_file_put_contents("$tempDir/$id.har", $har);
      unset($har);
      $file = "$tempDir/$id.har.gz";
      if (is_file($file)) {
        $file = realpath($file);
        $remoteFile = "$name/$id.har.gz";
        $bucket = 'httparchive';
        har_log("{$id} - Uploading to $remoteFile");
        if (gsUpload($file, $bucket, $remoteFile)) {
          $ok = true;
        } else {
          har_log("{$id} - error uploading HAR");
        }
        unlink($file);
      } else {
        har_log("{$id} - error saving HAR");
      }
    } else {
      har_log("{$id} - error generating HAR");
    }
    
    // clean up the test if we restored it
    if ($restored)
      delTree($testPath, true);
  } else {
    har_log("{$id} - error restoring test");
  }
  
  return $ok;
}

/**
* Upload the given file to Google Storage
* 
* @param mixed $file
* @param mixed $remoteFile
*/
function gsUpload($file, $bucket, $remoteFile) {
  $ret = false;
  $key = 'GOOGT4X7CFTWS2VWN2HT';
  $secret = 'SEWZTyKZH6dNbjbT2CHg5Q5pUh5Y5+iinj0yBFB4';
  $server = 'storage.googleapis.com';
  $s3 = new S3($key, $secret, false, $server);
  $metaHeaders = array();
  $requestHeaders = array();
  if ($s3->putObject($s3->inputFile($file, false), $bucket, $remoteFile, S3::ACL_PUBLIC_READ, $metaHeaders, $requestHeaders))
    $ret = true;
  return $ret;
}

function MarkDone() {
  global $tempDir;
  global $name;
  $marker = "$tempDir/done.txt";
  file_put_contents($marker, "");
  $file = realpath($marker);
  $remoteFile = "$name/done.txt";
  $bucket = 'httparchive';
  gsUpload($file, $bucket, $remoteFile);
}

function har_log($msg) {
  global $log;
  logMsg($msg, $log, true);
}
?>
