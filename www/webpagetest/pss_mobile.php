<?php
if( !defined('BARE_UI') )
    define('BARE_UI', true);
include 'common.inc';

// load the secret key (if there is one)
$secret = '';
$keys = parse_ini_file('./settings/keys.ini', true);
if( $keys && isset($keys['server']) && isset($keys['server']['secret']) )
  $secret = trim($keys['server']['secret']);
    
$connectivity = parse_ini_file('./settings/connectivity.ini', true);
$locations = LoadLocations();
$loc = ParseLocations($locations);

$preview = false;
if( array_key_exists('preview', $_GET) && strlen($_GET['preview']) && $_GET['preview'] )
    $preview = true;
$mps = false;
if (array_key_exists('mps', $_REQUEST))
    $mps = true;

$page_keywords = array('Comparison','Webpagetest','Website Speed Test','Page Speed');
$page_description = "Comparison Test$testLabel.";
?>

<!DOCTYPE html>
<html>
    <head>
        <title>WebPagetest - Comparison Test</title>
        <?php $gaTemplate = 'PSS'; include ('head.inc'); ?>
    </head>
    <body>
        <div class="page">
            <?php
            $navTabs = array(   'New Comparison' => FRIENDLY_URLS ? '/compare' : '/pss.php' );
            if( array_key_exists('pssid', $_GET) && strlen($_GET['pssid']) )
                $navTabs['Test Result'] = FRIENDLY_URLS ? "/result/{$_GET['pssid']}/" : "/results.php?test={$_GET['pssid']}";
            $navTabs += array(  'PageSpeed Service Home' => 'http://code.google.com/speed/pss', 
                                'Sample Tests' => 'http://code.google.com/speed/pss/gallery.html',
                                'Sign Up!' => 'https://docs.google.com/a/google.com/spreadsheet/viewform?hl=en_US&formkey=dDdjcmNBZFZsX2c0SkJPQnR3aGdnd0E6MQ');
            $tab = 'New Comparison';
            include 'header.inc';
            ?>
            <form name="urlEntry" action="/runtest.php" method="POST" enctype="multipart/form-data" onsubmit="return PreparePSSTest(this)">
            
            <input type="hidden" name="private" value="1">
            <input type="hidden" name="view" value="pss">
            <input type="hidden" name="label" value="">
            <input type="hidden" name="video" value="1">
            <input type="hidden" name="shard" value="1">
            <input type="hidden" name="priority" value="0">
            <input type="hidden" name="timeline" value="1">
            <input type="hidden" name="fvonly" value="1">
            <input type="hidden" name="sensitive" value="1">
            <input type="hidden" name="web10" value="1">
            <input type="hidden" id="script" name="script" value="setDnsName&#09;%HOSTR%&#09;spdypss-proxy.ext.google.com&#10;overrideHost&#09;%HOSTR%&#09;spdypss-proxy.ext.google.com&#10;navigate&#09;%URL%">
            <input type="hidden" name="runs" value="10">
            <input type="hidden" name="discard" value="3">
            <input type="hidden" name="bulkurls" value="">
            <input type="hidden" name="vo" value="<?php echo $owner;?>">
            <?php
            if( strlen($secret) ){
              $hashStr = $secret;
              $hashStr .= $_SERVER['HTTP_USER_AGENT'];
              $hashStr .= $owner;
              
              $now = gmdate('c');
              echo "<input type=\"hidden\" name=\"vd\" value=\"$now\">\n";
              $hashStr .= $now;
              
              $hmac = sha1($hashStr);
              echo "<input type=\"hidden\" name=\"vh\" value=\"$hmac\">\n";
            }
            ?>
            <h2 class="cufon-dincond_black"><small>Measure your Mobile site performance when optimized by <a href="http://code.google.com/speed/pss">PageSpeed Service</a></small></h2>
            <div id="test_box-container">
                <div id="analytical-review" class="test_box">
                    <ul class="input_fields">
                        <?php
                        $default = 'Enter a Website URL';
                        if (array_key_exists('url', $_GET) && strlen($_GET['url']))
                            $default = trim($_GET['url']);
                        echo "<li><input type=\"text\" name=\"testurl\" id=\"testurl\" value=\"$default\" class=\"text large\" onfocus=\"if (this.value == this.defaultValue) {this.value = '';}\" onblur=\"if (this.value == '') {this.value = this.defaultValue;}\"></li>\n";
                        ?>
                        <li>
                            <label for="location">Location</label>
                            <select name="where" id="location">
                                <?php
                                $lastGroup = null;
                                foreach($loc['locations'] as &$location)
                                {
                                    $selected = '';
                                    if( $location['checked'] )
                                        $selected = 'selected';
                                        
                                    if (array_key_exists('group', $location) && $location['group'] != $lastGroup) {
                                        if (isset($lastGroup))
                                            echo "</optgroup>";
                                        if (strlen($location['group'])) {
                                            $lastGroup = $location['group'];
                                            echo "<optgroup label=\"" . htmlspecialchars($lastGroup) . "\">";
                                        } else
                                            $lastGroup = null;
                                    }

                                    echo "<option value=\"{$location['name']}\" $selected>{$location['label']}</option>";
                                }
                                if (isset($lastGroup))
                                    echo "</optgroup>";
                                ?>
                            </select>
                            <?php if( $settings['map'] ) { ?>
                            <input id="change-location-btn" type=button onclick="SelectLocation();" value="Select from Map">
                            <?php } ?>
                            <span class="cleared"></span>
                        </li>
                        <li>
                            <label for="browser">Browser</label>
                            <select name="browser" id="browser">
                                <?php
                                foreach( $loc['browsers'] as $key => &$browser )
                                {
                                    $selected = '';
                                    if( $browser['selected'] )
                                        $selected = 'selected';
                                    echo "<option value=\"{$browser['key']}\" $selected>{$browser['label']}</option>\n";
                                }
                                ?>
                            </select>
                        </li>
                        <li>
                            <label for="location">Connection</label>
                            <select name="location" id="connection">
                                <?php
                                foreach( $loc['connections'] as $key => &$connection )
                                {
                                    $selected = '';
                                    if( $connection['selected'] )
                                        $selected = 'selected';
                                    echo "<option value=\"{$connection['key']}\" $selected>{$connection['label']}</option>\n";
                                }
                                ?>
                            </select>
                            <br>
                            <table class="configuration hidden" id="bwTable">
                                <tr>
                                    <th>BW Down</th>
                                    <th>BW Up</th>
                                    <th>Latency</th>
                                    <th>Packet Loss</th>
                                </tr>
                                <tr>
                                    <?php
                                        echo '<td class="value"><input id="bwDown" type="text" name="bwDown" style="width:3em; text-align: right;" value="' . $loc['bandwidth']['down'] . '"> Kbps</td>';
                                        echo '<td class="value"><input id="bwUp" type="text" name="bwUp" style="width:3em; text-align: right;" value="' . $loc['bandwidth']['up'] . '"> Kbps</td>';
                                        echo '<td class="value"><input id="latency" type="text" name="latency" style="width:3em; text-align: right;" value="' . $loc['bandwidth']['latency'] . '"> ms</td>';
                                        echo '<td class="value"><input id="plr" type="text" name="plr" style="width:3em; text-align: right;" value="' . $loc['bandwidth']['plr'] . '"> %</td>';
                                    ?>
                                </tr>
                            </table>
                        </li>
                        <li>
                            <label for="wait">Expected Wait</label>
                            <span id="wait"></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div id="start_test-container">
                <p><input id="start_test-button" type="submit" name="submit" value="" class="start_test"></p>
            </div>
            <div class="cleared"><br></div>

            <div id="location-dialog" style="display:none;">
                <h3>Select Test Location</h3>
                <div id="map">
                </div>
                <p>
                    <select id="location2">
                        <?php
                        $lastGroup = null;
                        foreach($loc['locations'] as &$location)
                        {
                            $selected = '';
                            if( $location['checked'] )
                                $selected = 'SELECTED';
                            if (array_key_exists('group', $location) && $location['group'] != $lastGroup) {
                                if (isset($lastGroup))
                                    echo "</optgroup>";
                                if (strlen($location['group'])) {
                                    $lastGroup = $location['group'];
                                    echo "<optgroup label=\"" . htmlspecialchars($lastGroup) . "\">";
                                } else
                                    $lastGroup = null;
                            }
                                
                            echo "<option value=\"{$location['name']}\" $selected>{$location['label']}</option>";
                        }
                        if (isset($lastGroup))
                            echo "</optgroup>";
                        ?>
                    </select>
                    <input id="location-ok" type=button class="simplemodal-close" value="OK">
                </p>
            </div>
            
            </form>

            <?php
            include('footer.inc'); 
            ?>
        </div>

        <script type="text/javascript">
        <?php 
            echo "var maxRuns = {$settings['maxruns']};\n";
            echo "var locations = " . json_encode($locations) . ";\n";
            echo "var connectivity = " . json_encode($connectivity) . ";\n";

            $sponsors = parse_ini_file('./settings/sponsors.ini', true);
            if( strlen($GLOBALS['cdnPath']) )
            {
                foreach( $sponsors as &$sponsor )
                {
                    if( isset($sponsor['logo']) )
                        $sponsor['logo'] = $GLOBALS['cdnPath'] . $sponsor['logo'];
                    if( isset($sponsor['logo_big']) )
                        $sponsor['logo_big'] = $GLOBALS['cdnPath'] . $sponsor['logo_big'];
                }
            }
            echo "var sponsors = " . json_encode($sponsors) . ";\n";
           
        ?>
        </script>
        <script type="text/javascript" src="<?php echo $GLOBALS['cdnPath']; ?>/js/test.js?v=<?php echo VER_JS_TEST;?>"></script> 
        <script type="text/javascript">
            wptStorage['testBrowser'] = 'Chrome';
            function PreparePSSTest(form)
            {
                var url = form.testurl.value;
                if( url == "" || url == "Enter a Website URL" )
                {
                    alert( "Please enter an URL to test." );
                    form.url.focus();
                    return false
                }
                var proto = url.substring(0, 6).toLowerCase();
                if (proto == 'https:') {
                    alert( "HTTPS sites are not currently supported" );
                    return false;
                }
                
                form.label.value = 'PageSpeed Service Comparison for ' + url;
                
                var batch = "Original=" + url + " noscript\nOptimized=" + url;
                form.bulkurls.value=batch;
                
                return true;
            }
        </script>
    </body>
</html>


<?php
/**
* Load the location information
* 
*/
function LoadLocations()
{
    $locations = parse_ini_file('./settings/locations.ini', true);
    // only include the mobile browsers
    foreach ($locations as $index => &$loc) {
        if( array_key_exists('browser', $loc) ) {
          if (!array_key_exists('type', $loc) ||
              (stripos($loc['type'], 'nodejs') === false &&
               stripos($loc['type'], 'mobile') == false)) {
               unset($locations[$index]);
          }
        }
    }
    FilterLocations( $locations, 'pss' );
    
    // strip out any sensitive information
    foreach( $locations as $index => &$loc )
    {
        if( isset($loc['browser']) )
        {
            $testCount = 26;
            if (array_key_exists('relayServer', $loc)) {
                $loc['backlog'] = 0;
                $loc['avgTime'] = 30;
                $loc['testers'] = 1;
                $loc['wait'] = ceil(($testCount * 30) / 60);
            } else {
                GetPendingTests($index, $count, $avgTime);
                if( !$avgTime )
                    $avgTime = 30;  // default to 30 seconds if we don't have any history
                $loc['backlog'] = $count;
                $loc['avgTime'] = $avgTime;
                $loc['testers'] = GetTesterCount($index);
                $loc['wait'] = -1;
                if( $loc['testers'] )
                {
                    if( $loc['testers'] > 1 )
                        $testCount = 16;
                    $loc['wait'] = ceil((($testCount + ($count / $loc['testers'])) * $avgTime) / 60);
                }
            }
        }
        
        unset( $loc['localDir'] );
        unset( $loc['key'] );
        unset( $loc['remoteDir'] );
        unset( $loc['relayKey'] );
    }
    
    return $locations;
}

?>