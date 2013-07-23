<?php
set_time_limit(0);
$url = "http://127.0.0.1/runtest.php?k=9700b3bfc2514486ba3c8aa1e3dab27b&url=www.google.com&f=xml";

for ($run = 1; $run <= 10; $run++) {
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        file_get_contents($url);
    }
    $end = microtime(true);
    echo "Run $run: " . number_format($end - $start, 4) . " seconds\n";
}
?>
