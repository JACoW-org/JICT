#!/usr/bin/php
<?php

// 2019.02.21 by Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'jict', '1.0' );
$cfg =config( 'make_page_programme' );

require_lib( 'programme', '1.0' );

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-verbose': 
			$cfg['verbose'] =$argv[++$i]; 

                        echo "verbose level = $cfg[verbose]\n";
			break;

		case '-quiet': 
			$cfg['verbose'] =0;
			break;
        }
}

$Programme =new Programme;

$Programme->config( $cfg );

// Show configuration
if ($cfg['verbose'] > 2) $Programme->config();


if (!need_file( APP_ABSTRACTS ) || !need_file( APP_PROGRAMME )) {
	echo "\n\nTry to run indico_importer/make.php\n\n";
	die;
}

$Programme->load();

$Programme->prepare();
$Programme->make();
$Programme->make_abstracts();

$Programme->make_rooms_css();

//$Programme->make_ics();

?>