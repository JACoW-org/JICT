<?php

// 2022.08.30 bY Stefano.Deiuri@Elettra.Eu

$cws_config =array(
	'global' =>array(	
		'conf_name'			=>'', // IPAC XX
		'conf_url'			=>'', // https://ipac_xx.org/
		
		'mode'				=>'', // Indico OR SPMS

		'indico_server_url' =>'', // https://indico.jacow.org
		'indico_event_id'	=>'', // XX
		'indico_token'		=>'', // indp_....

		'indico_client_id'	=>"",
		'indico_secret'		=>"",
		
		'indico_oauth' 		=>[
			'client_id' =>"",
			'client_secret'    =>"",
			'redirect_uri' =>""
			],

		'cws_timezone'		=>'', // Australia/Melbourne
		
		'root_url'			=>'', // https://www.test.eu/ipac_xx
		'root_path'			=>'', // /var/www/html/ipac_xx';

		'location'			=>'', // Malmö, Sweden
		'date_start'		=>'', // 2099-05-19
		'date_end'			=>'', // 2099-05-23

        'dates' =>[
            'conference' =>[ 'from' =>'', 'to' =>'' ],
            'abstracts_submission' =>[ 'from' =>'', 'to' =>'' ],
            'papers_submission' =>[ 'from' =>'', 'to' =>'' ],
            'registration' =>[ 'from' =>'', 'to' =>'', 'early_bird_to' =>'' ],
            'proceeding_office' =>[ 'from' =>'', 'to' =>'' ],
			],

		'data_path'			=>'{root_path}/data',
		'out_path'			=>'{root_path}/html',
		'tmp_path'			=>'{root_path}/tmp',
		
		'cron_enabled'		=>true,
		
		'wget_options'		=>'-q',
		
		'debug'				=>false,
		'colored_output'	=>false,
		'verbose'			=>2,
		'echo_mode'			=>false,

		'chart_type'		=>'LineChart', // LineChart or AreaChart
		'chart_width'		=>800,
		'chart_height'		=>300,
	
		'logo'			=>'logo.png',

        'colors'    =>[
            'primary'		=>'#0062a3',
            'secondary'	    =>'#d73d06',
            'r' 			=>'#FF4136',
            'y' 			=>'#FFDC00',
            'g' 			=>'#2ECC40',
            'a' 			=>'#ff14b1', //990099',
            'nofiles' 	    =>'#555555',
            'files' 		=>'#7FDBFF',
            'removed' 	    =>'#000000',
            'qaok'		    =>'#0074D9',
            ],
		
		// Labels
		'labels'			=>[
			'files'		=>'Ready for processing',
			'a'			=>'Assigned to an Editor',
			'g'			=>'Paper successfully processed',
			'y'			=>'Please check your e-mail',
			'r'			=>'Please check your e-mail',
			'nofiles' 	=>'No valid files uploaded yet'	
			],

		'label_files'		=>'Ready for processing',
		'label_a'			=>'Assigned to an Editor',
		'label_g'			=>'Paper successfully processed',
		'label_y'			=>'Please check your e-mail',
		'label_r'			=>'Please check your e-mail',
		'label_nofiles' 	=>'No valid files uploaded yet'
	),
	
	//-------------------------------------------------------------------------------------------------
	'indico_importer' =>array(
		'name'					=>'Indico Importer',
		'cron'					=>'*:00',

		'cache_time'			=>600, // useful for test
		'skip_sessions'			=>false,
        'papers_hidden_sessions' =>[],
        'refs_hidden_sessions' =>[],
		
		'tmp_path'				=>'{tmp_path}/indico',
		
		// out
		'in_papers'				=>'{app_data_path}/papers.json',

		// out
		'out_papers'			=>'{app_data_path}/papers.json',
		'out_abstracts'			=>'{app_data_path}/abstracts.json',
		'out_persons'			=>'{app_data_path}/persons.json',
		'out_affiliations'		=>'{app_data_path}/affiliations.json',		
		'out_posters'			=>'{app_data_path}/posters.json',
		'out_programme'			=>'{app_data_path}/programme.json',
		'out_editing_tags'		=>'{app_data_path}/editing_tags.json',
		'out_authors'			=>'{data_path}/authors.json',

		'export_refs'			=>'{root_path}/exports/refs.csv',
		'export_transp'			=>'{out_path}/transparencies.csv'
	    ),

	//-------------------------------------------------------------------------------------------------
	'indico_stats_importer' =>array(
		'name'				    =>'Indico Statistics Importer',
		'cron'				    =>'*:05',

		'cache_time'			=>0, // useful for test

        'tmp_path'			    =>'{tmp_path}/indico',
	
		// in
//		'in_po'				    =>'{data_path}/po.json',
		'in_papers'			    =>'{data_path}/papers.json',
		'in_stats'				=>'{data_path}/stats.json',
		'in_last_nums'			=>'{data_path}/last_nums.json',
		'in_revisions'			=>'{data_path}/papers-revisions.json',

		// out
		'out_editors'			=>'{data_path}/editors.json',
		'out_stats'				=>'{data_path}/stats.json',
		'out_abstracts_stats'	=>'{data_path}/abstracts_stats.json',
        'out_registrants'	    =>'{data_path}/registrants.json',
		'out_last_nums'			=>'{data_path}/last_nums.json',
		'out_revisions'			=>'{data_path}/papers-revisions.json',
	    ),

	//-------------------------------------------------------------------------------------------------
	'data_bak' =>array(
		'name'			=>'Data Backup',
		'cron'			=>'*:59',
	),

    //-------------------------------------------------------------------------------------------------
	'page_dashboard' =>array(
		'allow_roles'		=>[ '*' ],

		'name'				    =>'Dashboard',

        'in_abstracts_stats'	=>'{data_path}/abstracts_stats.json',
        'in_registrants'	    =>'{data_path}/registrants.json',
        'in_stats'			    =>'{data_path}/stats.json',

		'template'			    =>'template.html',

		// out
		'default_page'		    =>'{app}/index.php'        
	    ),

	//-------------------------------------------------------------------------------------------------
	'make_colors_css' =>array(
		'name'				=>'Colors Style Sheet',
		'cron'				=>'*:05',

        'out_css'           =>'{out_path}/colors.css'
	),

	//-------------------------------------------------------------------------------------------------
/* 	'make_chart_abstracts' =>array(
		'name'				=>'Chart Abstracts Submission',
		'cron'				=>'*:10',
		
		'xtract'			=>'abstractsubmissions',
		'y_title'			=>'Abstracts',
		'startdate'			=>false, // ex. Y-m-d '2017-1-1', m =(month -1) 1 = feb
		
		// in
		'chart_js'			=>'chart.js',
		'chart_html'		=>'chart.html',

		// out
		'out_js'			=>'chart_abstracts.js',
		'out_html'			=>'chart_abstracts.html'
	), */
		
	//-------------------------------------------------------------------------------------------------
/* 	'make_chart_papers' =>array(
		'name'				=>'Chart Papers Submission',
		'cron'				=>'*:10',
		
		'y_title'			=>'Papers',
		'startdate'			=>false,
		
		// in		
		'in_stats'			=>'{data_path}/stats.json', // Indico

		'chart_js'			=>'chart.js',
		'chart_html'		=>'chart.html',
		
		// out
		'out_js'			=>'chart_papers.js',
		'out_html'			=>'chart_papers.html'
	),	 */	
		
	//-------------------------------------------------------------------------------------------------
/* 	'make_chart_registrants' =>array(
		'name'				=>'Chart Registrants',
		'cron'				=>'*:10',
		
		'xtract'			=>'regstats',
		'y_title'			=>'Registrants',
		'startdate'			=>false,
		
		// in		
		'chart_js'			=>'chart.js',
		'chart_html'		=>'chart.html',

		// out
		'out_js'			=>'chart_registrants.js',
		'out_html'			=>'chart_registrants.html',
        
        'out_registrants'   =>'{data_path}/registrants.json'
	),		 */
		
	//-------------------------------------------------------------------------------------------------
	'page_authors' =>array(
		'name'				=>'Authors',

		'allow_roles'		=>[ '*' ],

		// in
		'in_authors'		=>'{data_path}/authors.json',
		'in_authors_check'	=>'{data_path}/author_reception.json',

		'template'			=>'template.html',

		// out
		'default_page'		=>'{app}/index.php'
	), 

	//-------------------------------------------------------------------------------------------------
	'page_statistics' =>array(
		'name'				=>'Statistics',
		'allow_roles'		=>[ '*' ],

		// in
		'in_stats'			=>'{data_path}/stats.json',
		'in_editors'		=>'{data_path}/editors.json',
		'in_papers'			=>'{data_path}/papers.json',
		'in_editing_tags'	=>'{data_path}/editing_tags.json',
		'in_authors_check'	=>'{data_path}/author_reception.json',
        'in_posters_status' =>'{data_path}/posters-status.json',

		'template'			=>'template.html',

		// out
		'default_page'		=>'{app}/index.php'
	), 

	//-------------------------------------------------------------------------------------------------
	'page_slides' =>array(
		'name'				=>'Slides',
		'allow_roles'		=>[ 'WSA', 'WSP' ],		

//		'cron'				=>'*:10',
//		'post_load_f'		=>false,

		// in
		'in_programme'		=>'{data_path}/programme.json',
		'in_status'			=>'{data_path}/talks_status.json',

		'template'			=>'template.html',

		// out
		'out_status'		=>'{data_path}/talks_status.json',
		
		'default_page'		=>'{app}/index.php'
	), 

	//-------------------------------------------------------------------------------------------------
	'page_papers' =>array(
		'name'				=>'Papers',

		'allow_roles'		=>[ '*' ],

		'cron'				=>'*:10',

		'post_load_f'		=>false,

		// in
		'in_papers'			=>'{data_path}/papers.json',
		'in_authors'		=>'{data_path}/authors.json',
		'in_registrants'	=>'{data_path}/registrants.json',
		'in_authors_check'	=>'{data_path}/author_reception.json',
        'in_posters_status' =>'{data_path}/posters-status.json',
        'in_pdf_problems'   =>'{data_path}/papers-problems.json',

		'template'			=>'template.html',

		// out
		'default_page'		=>'{app}/index.php'
	), 

	//-------------------------------------------------------------------------------------------------
	'page_authors_check' =>array(
		'name'			=>'Authors Check',
		'cron'			=>'*:05',
//		'cron_options'	=>'-f',

		'allow_roles'	=>[ 'WSA', 'WAR' ],

		'filter'		=>[ 'key' =>'status_qa', 'value' =>'QA Approved' ],
	
		// in
		'in_papers'		=>'{data_path}/papers.json',
		'in_data'		=>'{data_path}/author_reception.json',
		
		'template'		=>'template.html',
		
		// out
		'default_page'	=>'{app}/index.php',
		'out_data'		=>'{data_path}/author_reception.json'
	), 

	//-------------------------------------------------------------------------------------------------
	'make_page_participants' =>array(
		'name'				=>'Registrants',
		'cron'				=>'*:12',
		
		// chart
		'xtract'			=>'regstats',
		'y_title'			=>'Registrants',
		'startdate'			=>false,

		// list
		'xtract2'			=>'attendees',
		'chart_var'			=>'Registrants',
		
		// in		
		'chart_js'			=>'chart.js',
		'template_html'		=>'template.html',
		'css'				=>'participants.css',
		
		// out
		'out_js'			=>'chart_participants.js',
		'out_css'			=>'participants.css',		
		'out_html'			=>'participants.html'
	),
		
	//-------------------------------------------------------------------------------------------------
	'make_page_programme' =>array( 
		'name'			=>'Programme',
		'cron'			=>'*:10',
		
		'img_path'		=>'programme/images',
		'base_url'		=>'programme.php',
		'tab_w'			=>" width='850'",

		'colors_css'	=>true,

        'day_link_fmt'	=>"javascript:day({day});",
        
		'default_page'	=>'html/programme.php',
		
		'sessions'		=>'collapsed',
        'sessions_details' =>true,
		'special_sessions_class' =>false,
		
        'coffee_break_time_end' =>[ '11:00' ],
        'lunch_break_time_end' =>[ '14:30' ],

		'tsz_adjust'	=>0,
	
		// in
		'abstracts'		=>'{app_data_path}/abstracts.json',
		'programme'		=>'{app_data_path}/programme.json',

		// out
		'out_path'		=>'{out_path}/programme',
		'ics'			=>'{app_out_path}/programme.ics'
	),
		
	//-------------------------------------------------------------------------------------------------
	'app_paper_status' =>array(
		'name'			=>'App Paper Status',

		'echo_mode'		=>'web',

		'data_path'		=>'{data_path}/{app}',
		'tmp_path'		=>'{tmp_path}/{app}',

		'default_page'	=>'{app}/index.php',
		
		'colors_css'	=>true,
		
		'label_removed'	=>'Removed',

		// in
		'in_papers'		=>'{data_path}/papers.json',

		'in_template_html'	=>'template.html',

		// out
		'log'			=>'{app_data_path}/usage.log'
	),
		
	//-------------------------------------------------------------------------------------------------
	'app_poster_police' =>array(
		'name'			=>'App Poster Police',

		'allow_roles'		=>[ 'WSA', 'WPP' ],

		'dummy_mode'	=>false,
		'pp_manager'	=>false, // PosterPolice PersonID
		'password'		=>false,

		'verbose'		=>false,
		'echo_mode'		=>'web',
		
		'data_path'		=>'{data_path}/{app}',

		'default_page'	=>'{app}/index.php',
		
		// in
		'in_posters'	=>'{data_path}/posters.json',
		
		// out
		'out_posters'	=>'{data_path}/posters.json',
        'out_posters_status'    =>'{data_path}/posters-status.json'
	),
	
	
	//-------------------------------------------------------------------------------------------------
	'cis' =>[
		'name'				=>'Conference Information System (CIS Admin)',
		'allow_roles'		=>[ 'WSA', 'WCM' ],

		'echo_mode'			=>'web',
		
		'default_page'		=>'{app}/admin.php',

		// in
		'in_clients'			=>'{data_path}/cis-clients.json',
		'in_contents'			=>'{data_path}/cis-contents.json',

		// out
		'out_clients'			=>'{data_path}/cis-clients.json',
		'out_contents'			=>'{data_path}/cis-contents.json'
		],	


	//-------------------------------------------------------------------------------------------------
	'page_edots' =>array(
		'name'				=>'Paper Processings Status (Dotting Board)',

		'echo_mode'			=>'web',
		
		'default_page'		=>'{app}/index.html',
		
		'colors_css'		=>true,		
		
		'change_page_delay' =>10, // seconds
		'reload_data_delay' =>120, // seconds			
		'board_rows'		=>false,
		'board_cols'		=>false,

		'paper_status_url' 	=>false,
		'paper_status_qrcode' 	=>true,
		'qrcode_cells'		=>4,

		// in
		'in_papers'			=>'{data_path}/papers.json'
	),	
		
	//-------------------------------------------------------------------------------------------------
	'page_po_status' =>array(
		'name'			=>'Proceedings Office Status',

		'echo_mode'		=>'web',

		'default_page'	=>'{app}/index.html',

		'colors_css'	=>true,
		
		'history_date_start' =>false,
		
		'label_g'		=>'GREEN DOT (successfully processed)',
		'label_y'		=>'YELLOW DOT (wait author approval)',
		'label_r'		=>'RED DOT (unsuccessfully processed)',
		
		// in
		'in_editors'	=>'{data_path}/editors.json',
		'in_papers'		=>'{data_path}/papers.json',
		'in_stats'		=>'{data_path}/stats.json'
	),
		
	//-------------------------------------------------------------------------------------------------
	'barcode' =>array(
		'name'			=>'BarCode Page',
		
		'echo_mode'		=>'web',
		
		'apk'			=>'JACoW_BarCode.apk',
		
		'data_url'		=>'{root_url}/data/{app}', 
		'qrcode_url'	=>'{root_url}/html/{app}', 

		'default_page'	=>'{app}/index.php',
		
		// out
		'out_path'		=>'{out_path}/{app}',
		'qrcode_path'	=>'{out_path}/{app}',
		'data_path'		=>'{data_path}/{app}',
		'log'			=>'{app_data_path}/usage.log',
		
		// in
		'template_html'	=>'template.html',
		'po'			=>'{data_path}/po.json'
	)
);


if (empty($cws_config['global']['root_path'])) require( 'conference-config.php' );

define( 'ROOT_PATH', $cws_config['global']['root_path'] );

$cws_echo_mode =false;
	
//----------------------------------------------------------------------------
function require_lib( $_name, $_version ) {
 $fname =ROOT_PATH .'/libs/' .$_name .'-' .$_version .'.lib.php';
 
 if (!file_exists($fname)) {
	echo "\n\nERROR: unable to load lib $_name-$_version\n\nPlease check the ROOT_PATH!\n\n";
 }
 
 require_once( $fname );
}

?>