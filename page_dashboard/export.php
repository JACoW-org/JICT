<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.12.21 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_dashboard' );
$cfg['verbose'] =0;

$dates =$cws_config['global']['dates'];

$Indico =new INDICO( $cfg );
$Indico->load();


$Indico->data['papers']['by_dates'] =[];
$last =0;
$papers_stats =[];

$ts_from =strtotime($dates['papers_submission']['from']);
$ts_deadline =strtotime($dates['papers_submission']['deadline']);
$ts_to =strtotime($dates['papers_submission']['to']);

foreach ($Indico->data['stats'] as $ymdh =>$x) {
    if (!empty($x['ts']) && $x['ts'] >= $ts_from && $x['ts'] <= $ts_to) {
        $date =substr( $ymdh, 0, 10 );
        $papers_stats[$date] =$x;
        //print_r( $x );
    }
}

foreach ($papers_stats as $date =>$x) {
    $ts =$x['ts'];
    
    $val =$x['files'] +$x['processed'] -$last;
    if ($val < 0) $val =0;
    $Indico->data['papers']['by_dates'][$date] =$val;

    $last =$x['files'] +$x['processed'];
}

$ret =[
    'conf_name' =>$cws_config['global']['conf_name'],

    'abstracts' =>[ 
        'dates' =>$dates['abstracts_submission'],
        'history' =>$Indico->data['abstracts_stats']['by_dates'],
        'count' =>array_sum($Indico->data['abstracts_stats']['by_dates']),
        'withdrawn' =>$Indico->data['abstracts_stats']['withdrawn']
        ],

    'registrants' =>[ 
        'dates' =>$dates['registration'],
        'history' =>$Indico->data['registrants']['stats']['by_dates'],
        'count' =>array_sum($Indico->data['registrants']['stats']['by_dates'])
        ],

    'papers' =>[ 
        'dates' =>$dates['papers_submission'],
        'history' =>$Indico->data['papers']['by_dates'],
        'count' =>$last
        ]
    ];

file_write_json( strtolower(str_replace( "'", "", $cws_config['global']['conf_name'] )) .'.json', $ret );

?>