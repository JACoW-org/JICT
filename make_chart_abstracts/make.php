#!/usr/bin/php
<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.08.08 - update for Indico

*/

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

    $chart =array();
    foreach ($Indico->data['abstracts_list'] as $x) {
        $date =date( 'Y-m-d', $x['ts'] );
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