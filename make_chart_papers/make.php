#!/usr/bin/php
<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.07.25 - update for Indico

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
    $Indico->load();

    $data =array();
    foreach ($Indico->data['stats'] as $ymdh =>$x) {
        $date =substr( $ymdh, 0, 10 );
        $data[$date] =$x['total'] -$x['nofiles'];
    }
    
    $chart =array();
    $last_value =0;
    foreach ($data as $date =>$x) {
        $chart[$date][0] =$x -$last_value;
        $last_value =$x;
    }

    $Indico->GoogleChart( $chart );

} else {
	require_lib( 'spms_importer', '1.0' );
	
	$SPMS =new SPMS_Importer( $cfg );
	$SPMS->GoogleChart();
}


?>
