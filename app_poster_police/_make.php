#!/usr/bin/php
<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.08.29 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'app_poster_police' );

$cfg['verbose'] =2;
$cfg['echo_mode'] =$cws_echo_mode ='console';
$cfg['in_programme'] ='../data/programme.json';

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-verbose': 
			$cfg['verbose'] =$argv[++$i]; 
			break;

		case '-quiet': 
			$cfg['verbose'] =0;
			break;

		case '-help':
			echo "options:\n"
				."\t-verbose n: set verbose level to n\n"
				."\n";
			break;
		}
}

$Indico =new INDICO( $cfg );
$Indico->load();

$Indico->export_posters();

?>