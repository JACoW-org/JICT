#!/usr/bin/php	
<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.07.13 - 1st version

*/

require( '../config.php' );

require_lib( 'cws', '1.0' );
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

$Indico->import_abstracts_list();
$Indico->import_posters();

$Indico->save_all([ 'save_empty' =>true ]);

/* foreach ([ 'programme', 'papers', 'abstracts', 'editing_tags', 'authors', 'persons' ] as $obj) {
    $Indico->save_file( $obj, 'out_' .$obj, strtoupper($obj), array( 'save_empty' =>true ));
} */

//$Indico->export_po(); 

$Indico->export_refs();

?>