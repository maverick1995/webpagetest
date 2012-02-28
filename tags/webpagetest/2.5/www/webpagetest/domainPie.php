<?php
include 'common.inc';
include 'domains.inc';
require_once('contentColors.inc');

include ("lib/jpgraph/jpgraph.php");
include ("lib/jpgraph/jpgraph_pie.php"); 
JpGraphError::SetErrLocale('prod');

// Get the various settings
$width = htmlspecialchars($_GET["width"]);
if( !$width )
    $width = 500;

$height = htmlspecialchars($_GET["height"]);
if( !$height )
    $height = 400;

$chartType = htmlspecialchars($_GET["type"]);
if( !strlen($chartType) )
    $chartType = 'Requests';
    
// walk through the requests and group them by mime type
$breakdown = getDomainBreakdown($id, $testPath, $run, $cached);
if( count($breakdown) )
{
    // build up the data set
    $values = array();
    $index = 0;
    ksort($breakdown);
    $labels = array();
    $colors = array();
    $lastTLD = "";
    $count = 0;
    $index = 0;
    foreach($breakdown as $domain => $data)
    {
        $labels[] = strrev($domain);
        if( !strcasecmp($chartType, 'Requests') )
            $values[] = $data['requests'];
        else if( !strcasecmp($chartType, 'Bytes') )
            $values[] = $data['bytes'];
    }
        
    $graph  = new PieGraph($width,$height);
    $graph->SetFrame(false);
    $graph->SetAntiAliasing();

    $pie = new PiePlot($values);

    // set  the actual labels for the wedges
    $pie->SetLabels($labels, 1.1); 

    // set other options
    $pie->SetGuideLines( true, true, true); 
    $graph->title->SetFont( FF_FONT2, FS_BOLD );
    $graph->title->Set( $chartType );
    $pie->ShowBorder(true, true); 
    $pie->SetSize(0.25);

    $graph->Add( $pie);
    $graph->img->SetExpired(false);
    $graph->Stroke(); 
}

?>