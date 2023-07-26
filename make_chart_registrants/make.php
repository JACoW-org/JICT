#!/usr/bin/php
<?php

// 2019.01.22 bY Stefano.Deiuri@Elettra.Eu

if (in_array( '--help', $argv )) {
	echo "Program options:\n"
		."\n";
	return;
}

require( '../config.php' );
require_lib( 'cws', '1.0' );

$cfg =config();

if (INDICO) {
    require_lib( 'indico', '1.0' );

    $Indico =new INDICO( $cfg );
    $Indico->import();

    $obj ='registrants';
    $Indico->save_file( $obj, 'out_' .$obj, strtoupper($obj) );

    $chart =array();
    foreach ($Indico->data['registrants']['registrants'] as $r) {
        $date =date( 'Y-m-d', $r['ts'] );
        if (empty($chart[$date][0])) $chart[$date][0] =1;
        else $chart[$date][0] ++;
    }

    ksort( $chart );

    $Indico->GoogleChart( $chart );

} else {
    require_lib( 'spms_importer', '1.0' );
    
    $SPMS =new SPMS_Importer( $cfg );
    $SPMS->GoogleChart();
}


?>
