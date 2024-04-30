#!/usr/bin/php	
<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.07.13 - 1st version

*/

require( '../config.php' );

require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );


$cfg =config();

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-verbose': 
			$cfg['verbose'] =$argv[++$i]; 
			break;

		case '-quiet': 
			$cfg['verbose'] =0;
			break;

		case '-r': 
		case '-refresh': 
			$cfg['cache_time'] =0; 
			break;

		case '-cleanup':
			$Indico =new INDICO( $cfg );
			return $Indico->cleanup();
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

$Indico->import();

$Indico->import_abstracts();
$Indico->import_posters();

$Indico->save_all([ 'save_empty' =>true ]);

// https://www.jacow.org/jict_ipac23/exports/refs.csv
if (false) {
	foreach ($Indico->data['papers'] as $pid =>$p) {
		$position =false;
	
		$doi_fname =sprintf( "%s/doi/%s.json", $cfg['data_path'], $pid );
		if (file_exists( $doi_fname)) {
			$doi =file_read_json( $doi_fname, true );
			if ($doi && !empty($doi['data']['attributes']['sizes'][0])) $position =trim(str_replace( ' pages', "", $doi['data']['attributes']['sizes'][0] ));
		}
		
		echo "$pid: $position\n";
		$p['position'] =$position;
	
		$Indico->data['papers'][$pid] =$p;
	}
}

$Indico->export_refs_v3();

?>
