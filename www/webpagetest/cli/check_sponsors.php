<?php
chdir('..');
include 'common.inc';
ignore_user_abort(true);
set_time_limit(1200);
error_reporting(E_ALL);

$locations = LoadLocationsIni();
$sponsors = parse_ini_file('./settings/sponsors.ini', true);
$banners = scandir('./custom');

echo "<h2>Banners with no matching location:</h2><ul>";
foreach ($banners as $banner) {
  if ($banner != '.' && $banner != '..')
    if (!isset($locations[$banner]))
      echo '<li>' . htmlspecialchars($banner) . '</li>';
}
echo "</ul>";
echo "<h2>Sponsors with no matching location:</h2><ul>";
foreach ($sponsors as $sponsor => $stuff) {
  $found = false;
  foreach ($locations as $location)
    if (isset($location['sponsor']) && $location['sponsor'] == $sponsor)
      $found = true;
  if (!$found)
      echo '<li>' . htmlspecialchars($sponsor) . '</li>';
}
echo "</ul>";
?>
