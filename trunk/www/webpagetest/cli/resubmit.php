<?php
chdir('..');
$debug = true;
include 'common.inc';
$ids = file('./cli/resubmit.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($ids as $id) {
    echo "$id - Processing...\n";
    $testPath = './' . GetTestPath($id);
    if (gz_is_file("$testPath/bulk.json")) {
        $bulk = json_decode(gz_file_get_contents("$testPath/bulk.json"), true);
        if (isset($bulk) && is_array($bulk) && array_key_exists('urls', $bulk)) {
            foreach($bulk['urls'] as $url) {
                if (array_key_exists('id', $url))
                    ResubmitTest($url['id']);
            }
        }
    } else {
        ResubmitTest($id);
    }
    if (is_file("$testPath/video.json.gz"))
        unlink("$testPath/video.json.gz");
}

function ResubmitTest($id) {
    echo "$id - Resubmitting...";
    $testPath = './' . GetTestPath($id);
    if (gz_is_file("$testPath/testinfo.json")) {
        $test = json_decode(gz_file_get_contents("$testPath/testinfo.json"), true);
        if (array_key_exists('job_file', $test) && 
            array_key_exists('location', $test) && 
            is_file("$testPath/test.job")) {
            if ($lock = LockLocation($test['location'])) {
                if (copy("$testPath/test.job", $test['job_file'])) {
                    $files = scandir($testPath);
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..' && strncasecmp($file, 'test', 4)) {
                            if (is_file("$testPath/$file"))
                                unlink("$testPath/$file");
                            elseif (is_dir("$testPath/$file"))
                                delTree("$testPath/$file");
                        }
                    }
                    AddJobFile($test['workdir'], $test['job'], $test['priority'], false);
                    $test['started'] = time();
                    unset($test['completeTime']);
                    gz_file_put_contents("$testPath/testinfo.json", json_encode($test));
                    echo "OK";
                } else {
                    echo "Failed to copy job file";
                }
                UnlockLocation($lock);
            } else {
                echo "Failed to lock location";
            }
        } else {
            echo "Invalid test";
        }
    } else {
        echo "Test not found";
    }
    echo "\n";
}
?>
