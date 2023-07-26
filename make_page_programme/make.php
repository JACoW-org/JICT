#!/usr/bin/php
<?php

// 2019.02.21 by Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'cws', '1.0' );
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

class IPAC23_Programme extends Programme {
    function session( &$ps, $sid, &$html ) {
        $page =false;

/*         if ($ps[0] == 'S20') $ps =array( 'S21', 'S20' );
        else if ($ps[0] == 'S22') $ps =array( 'S23', 'S22' ); */

        $page =$this->multi_session( $ps, $html );

        return $page;
    }
}
   

$Programme =new IPAC23_Programme;

$Programme->config( $cfg );

// Show configuration
if ($cfg['verbose'] > 2) $Programme->config();


if (!need_file( APP_ABSTRACTS ) || !need_file( APP_PROGRAMME )) {
	echo "\n\nTry to run indico_importer/make.php\n\n";
	die;
}

$Programme->load();

$P =&$Programme->programme['days']['2023-05-11']['1430_SalaGrande_THZG_209']['papers'];

$P['THZG.01'] =[
	'title' =>'Prize session presentation by the Chair',
	'time_from' =>'14:30',
	'time_to' =>'14:35'
	];
$P['THZG.02'] =[
	'title' =>'Award cerimony of the 2 best student posters; award ceremony of the Touschek prize, presentation by the Touschek prize winner',
	'time_from' =>'14:35',
	'time_to' =>'14:50'
	];
$P['THZG.03'] =[
	'title' =>'Award ceremony of the Frank Sacherer Prize',
	'time_from' =>'14:50',
	'time_to' =>'14:55'
	];
$P['THZG1_'] =[
	'title' =>'Award ceremony of the Gersh Budker Prize',
	'time_from' =>'15:10',
	'time_to' =>'15:15'
	];
$P['THZG2_'] =[
	'title' =>'Award ceremony of the Rolf Wideröe Prize',
	'time_from' =>'15:30',
	'time_to' =>'15:35'
	];

ksort($P);

$Programme->prepare();
$Programme->make();
$Programme->make_abstracts();

//$Programme->make_rooms_css();

//$Programme->make_ics();

?>