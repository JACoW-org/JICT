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
$cfg['in_status'] ='../data/posters-status.json';
$cfg['in_papers'] ='../data/papers.json';

if (!INDICO) {
    echo "Scripts available only for Indico!\n\n";
    exit;
}

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

$customs =$Indico->request( "/event/{id}/manage/contributions/api/fields", 'GET', false, 
    [ 'return_data' =>true, 'quiet' =>true, 'cache_time' =>86400 ]);

$cf_id =$cf_option_id =false;

foreach ($customs as $x) {
    if ($x['title'] == 'CAT_publish') {
        $cf_id ='custom_' .$x['id'];

        foreach ($x['field_data']['options'] as $o) {
            if (strtolower( $o['option']) == 'no') $cf_option_id =$o['id'];
        }
    }
}

if (empty($cf_id) || empty($cf_option_id)) {
    echo "ERROR: Unable to found the CAT_publish custom field!\n\n";
    return;
}

echo "$cf_id >> $cf_option_id\n";

//$Indico->api->config( 'header_content_type', false );

$i =1;
foreach ($Indico->data['status'] as $pcode =>$x) {
    if ($x['status'] == 'Fail' && !empty($Indico->data['papers'][$pcode])) {
        $pid =$Indico->data['papers'][$pcode]['id'];
        echo str_pad( $i, 3, '0', STR_PAD_LEFT) ." - $pcode | $pid | $x[status]... ";

        $result =$Indico->request( "/event/{id}/manage/contributions/$pid/fields/?friendly=true", 'PATCH', [ $cf_id =>$cf_option_id ], [ 'return_data' =>true, 'quiet' =>true ]);
//        $result =$Indico->request( "/event/{id}/manage/contributions/$pid/fields/?friendly=true", 'PATCH', "$cf_id=$cf_option_id", [ 'return_data' =>true, 'quiet' =>true ]);

        echo (empty($result[$cf_id]) ? 'ERROR (cf not updated)' : 'OK') ."\n";

        if (empty($result[$cf_id])) {
            print_r( $result );
            print_r( $Indico->api );
    
            return;
        }

        $i ++;
    }
}

?>