<?php
chdir('..');
include 'common.inc';
if (array_key_exists('id', $_REQUEST)) {
    $id = $_REQUEST['id'];
    $dir = './' . GetVideoPath($id, true);
    if (is_dir($dir)) {
        $lock = fopen("$dir/render.lock", 'w');
        if ($lock !== false)
            flock($lock, LOCK_EX);
        $tests = json_decode(gz_file_get_contents("$dir/testinfo.json"));
    }
}
if (isset($tests) && is_array($tests)) {
    $end = 0;
    foreach($tests as &$test) {
        if (array_key_exists('end', $test) && $test['end'] > $end)
            $end = $test['end'];
    }
    $end = intval(ceil($end / 100) * 100);
    if ($end > 0) {
        PrepareTemplate($tests, $layout);
        for ($ms = 0; $ms <= $end; $ms += 100) {
            RenderFrame($layout, $tests, $ms, $dir);
        }
    }
}
if (isset($lock) && $lock !== false) {
  flock($lock, LOCK_UN);
  fclose($lock);
}

/**
* Create the in-memory image and layout information
*     
* @param mixed $tests
* @param mixed $layout
*/
function PrepareTemplate(&$tests, &$layout) {
}

/**
* Render and individual frame of video
* 
* @param mixed $tests
* @param mixed $ms
* @param mixed $dir
*/
function RenderFrame(&$layout, &$tests, $ms, $dir) {
}
?>
