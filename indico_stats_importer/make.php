#!/usr/bin/php
<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.07.19 - 1st version

*/

require( '../config.php' );

require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );


$cfg =config( 'indico_stats_importer', true );

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-verbose': 
			$cfg['verbose'] =$argv[++$i]; 
			break;

		case '-quiet': 
			$cfg['verbose'] =0;
			break;

		case '-refresh': 
			$cfg['cache_time'] =0; 
			break;

 		case '-cleanup':
			$Indico =new INDICO( $cfg );
			return $Indico->cleanup( );
			break;

		case '-skip-abstracts':
			$cfg['skip-abstracts'] =true;
			break;

		case '-skip-registrants':
			$cfg['skip-registrants'] =true;
			break;


        case '-help':
			echo "Program options:\n"
				."\t-cleanup: clear cached data\n"
				."\t-verbose n: set verbose level to n\n"
				."\n";
			break;
		}
}


$Indico =new INDICO( $cfg );

$Indico->load();

$Indico->import_stats();
if (empty($cfg['skip-abstracts'])) {
	echo "\n";
	$Indico->import_abstracts();
}

if (empty($cfg['skip-registrants'])) {
	echo "\n";	
	$Indico->import_registrants();
}

echo "\n";	
$Indico->save_all([ 'save_empty' =>true ]);

?>