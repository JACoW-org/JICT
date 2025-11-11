<?php

// Wed, 17 Jul 2024 13:17:39 +0000

$folder_name ='jict_ipac25';

$cws_config['global']['root_path']		='/web/httpd/vhost-jacow.org/' .$folder_name;
$cws_config['global']['root_url']		='https://www.jacow.org/' .$folder_name;

$cws_config['global']['indico_server_url']  ='https://indico.jacow.org';
$cws_config['global']['indico_event_id']    =81;
$cws_config['global']['indico_token']	    ='';

$cws_config['global']['conf_name'] 		='IPAC25';
$cws_config['global']['conf_url']		='https://www.ipac25.org';
$cws_config['global']['logo']		    ='html/logo.png';


$cws_config['global']['indico_oauth'] =[
    'client_id'     =>'', // jict
    'client_secret' =>'',
    'redirect_uri'  =>'' // 'https://www.jacow.org/jict/indico_oauth.php?conf=' .substr( $folder_name, 5 )
    ];

$cws_config['global']['admin'] ='stefano.deiuri@elettra.eu';

$cws_config['global']['location'] ='Taipei, Taiwan';
$cws_config['global']['timezone'] ='Asia/Taipei';
$cws_config['global']['difftime_sec'] =8 *3600;

$cws_config['global']['dates'] =[
    'conference' =>[ 
        'from'              =>'2025-06-01', 
        'to'                =>'2025-06-06' 
        ],
    'abstracts_submission' =>[ 
        'from'              =>'2024-10-07',
        'deadline'          =>'2024-12-10 23:59:59',
        'to'                =>'2024-12-17' 
        ],
    'papers_submission' =>[ 
        'from'              =>'2025-04-20', 
        'deadline'          =>'2025-05-28',        
        'to'                =>'2025-06-06' 
        ],
    'registration' =>[ 
        'from'              =>'2024-10-15 00:00:00', 
        'early_bird_to'     =>'2025-02-28 23:59:59',
        'deadline'          =>'2025-05-06 23:59:59', 
        'to'                =>'2025-06-06 23:59:59',
        'chart_to_deadline' =>'2025-06-01 23:59:59'
        ],
    'proceedings_office' =>[
        'history_from'      =>'2025-05-28', 
        'from'              =>'2025-05-29', 
        'to'                =>'2025-06-06' 
        ]
    ];

$cws_config['cron']['enabled'] =true;

$cws_config['data_bak']['cron'] ='23:59';

$cws_config['indico_importer']['cron'] ='*:00';
$cws_config['make_page_programme']['cron'] ='*:00';

$cws_config['indico_stats_importer']['cron'] ='*:*5';
$cws_config['page_authors_check']['cron'] ='*:*5';
$cws_config['page_papers']['cron'] ='*:*5';

$cws_config['make_page_participants']['cron'] =false;
$cws_config['app_paper_status']['cron'] =false;
$cws_config['app_poster_police']['cron'] =false;
$cws_config['barcode']['cron'] =false;
$cws_config['make_chart_abstracts']['cron'] =false;
$cws_config['make_chart_papers']['cron'] =false;
$cws_config['make_chart_registrants']['cron'] =false;
$cws_config['make_colors_css']['cron'] =false;
$cws_config['page_authors']['cron'] =false;
$cws_config['page_statistics']['cron'] =false;
$cws_config['page_team']['cron'] =false;

$cws_config['data_bak']['cron_after'] =substr( $cws_config['global']['dates']['proceedings_office']['from'], 0, 10 );;

foreach (['make_page_participants', 'cis', 'barcode'] as $app) {
    $cws_config[$app]['hide'] =true;
}

// hidden sessions
$cws_config['hidden_sessions']
    =$cws_config['indico_importer']['papers_hidden_sessions']
    =$cws_config['indico_importer']['refs_hidden_sessions']
    =$cws_config['indico_importer']['posters_hidden_sessions'] 
    =$cws_config['page_edots']['hidden_sessions']
    =$cws_config['page_po_status']['hidden_sessions'] 
    =[ 'SUP', 'SUPM', 'SUPS', 'PRE' ];

$cws_config['indico_importer']['refs_final'] =false;

$cws_config['indico_stats_importer']['registrants_form_id'] =70;
$cws_config['indico_stats_importer']['registrants_load_extra_data']=1;


$cws_config['page_authors_check']['filter'] =[ 'key' =>'status', 'value' =>'g' ];

$cws_config['make_page_participants']['startdate'] =substr( $cws_config['global']['dates']['registration']['from'], 0, 10 );

$cws_config['make_page_programme']['tab_w']  =' width=\'100%\'';
$cws_config['make_page_programme']['sessions_details'] =true;


$cws_config['page_edots']['paper_status_qrcode'] =false;
$cws_config['page_edots']['paper_status_url']	=false;
$cws_config['page_edots']['board_cols'] ='10';
$cws_config['page_edots']['board_rows'] ='21';


$cws_config['page_dashboard']['order'] =[ 'papers', 'abstracts', 'registrants', 'country' ];

if ((empty($cws_config['global']['indico_token']))||(empty($cws_config['global']['indico_oauth']))) require( 'conference-secrets.php' );

?>
