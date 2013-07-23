<?php 
include 'common.inc';
set_time_limit(0);

$page_keywords = array('Log','History','Webpagetest','Website Speed Test');
$page_description = "History of website performance speed tests run on WebPagetest.";

$admin = true;
if ($supportsAuth && ((array_key_exists('google_email', $_COOKIE) && strpos($_COOKIE['google_email'], '@google.com') !== false)))
    $admin = true;

// shared initializiation/loading code
error_reporting(0);
$days = (int)$_GET["days"];
$from = $_GET["from"];
if( !strlen($from) )
    $from = 'now';
?>
<!DOCTYPE html>
<html>
    <head>
        <title>WebPagetest - PSS History</title>
        <?php include ('head.inc'); ?>
		<style type="text/css">
            div.content {line-height: 200%;}
            a {text-decoration: none;}
            a:hover {cursor: pointer;}
		</style>
    </head>
    <body>
        <div class="page">
            <?php
            include 'header.inc';
            if (!$admin) {
                echo "Access Denied";
            } else {
            ?>
            <div class="translucent" style="overflow:hidden;">
                <form style="text-align:center;" name="filterLog" method="get" action="/psstests.php">
                    View <select name="days" size="1">
                            <option value="1" <?php if ($days == 1) echo "selected"; ?>>1 Day</option>
                            <option value="7" <?php if ($days == 7) echo "selected"; ?>>7 Days</option>
                            <option value="30" <?php if ($days == 30) echo "selected"; ?>>30 Days</option>
                            <option value="182" <?php if ($days == 182) echo "selected"; ?>>6 Months</option>
                            <option value="365" <?php if ($days == 365) echo "selected"; ?>>1 Year</option>
                         </select> <input id="SubmitBtn" type="submit" value="Update List"><br>
                </form>
                <h4>Clicking on an url will bring you to the results for that test</h4>
			        <?php
			        // loop through the number of days we are supposed to display
			        $targetDate = new DateTime($from, new DateTimeZone('GMT'));
			        for($offset = 0; $offset <= $days; $offset++) {
				        $fileName = './logs/' . $targetDate->format("Ymd") . '.log';
                        $ok = true;
                        $lines = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				        if(count($lines)) {
					        $records = array_reverse($lines);
                            unset($lines);
					        foreach($records as $line) {
                                if (stripos($line, 'Service Comparison for ') !== false) {                                
                                    $parseLine = str_replace("\t", "\t ", $line);
                                    $fields = explode("\t", $parseLine);
                                    $date = strftime('%x %X', strtotime(trim($fields[0])) + ($tz_offset * 60));
                                    $id = trim($fields[4]);
                                    $label = $fields[11];
                                    $index = stripos($label, 'Service Comparison for ');
                                    if ($index !== false) {
                                        $url = htmlspecialchars(trim(substr($label, $index + 23)));
                                        if (strlen($url))
                                            echo $date . " - <a href=\"/result/$id/\">$url</a><br>";
                                    }
						        }
					        }
				        }
				        
				        // on to the previous day
				        $targetDate->modify('-1 day');
			        }
			        ?>
            </div>
            
            <?php } include('footer.inc'); ?>
        </div>
    </body>
</html>
