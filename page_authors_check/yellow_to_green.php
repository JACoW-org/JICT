<html>
<head>
<title>yellow to green</title>
<style>
    body { font-family: 'helvetica'; }
</style>
</head>
<body>
<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.05.07 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );
$cfg =config( 'page_authors_check' );
$cfg['verbose'] =false;

$Indico =new INDICO( $cfg );
$Indico->load();

$yellow_to_green =[];

foreach ($Indico->data['papers'] as $pcode =>$p) {
    if ($p['status'] == 'g' && $p['revision_count'] > 1 && $p['prev_status'] == 'y') {
        $ymd =date( 'Y-m-d', $p['status_ts'] );
        $yellow_to_green[$ymd][date( 'H:i', $p['status_ts'] )] =$pcode;
    }
}

krsort( $yellow_to_green );

foreach ($yellow_to_green as $day =>$papers) {
    echo sprintf( "<h1>%s (%d)</h1>\n", $day, count( $papers ));

    krsort($papers);

    foreach ($papers as $time =>$pcode) {
        echo "\t$time - $pcode<br>\n";
    }

    echo "\n";
}

?>
</body>
</html>