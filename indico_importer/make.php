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

$Indico->export_refs();

echo sprintf( "\nIndico's requests: %d (cache) / %d\n", $Indico->requests_cache_count, $Indico->requests_api_count );

?>