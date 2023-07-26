<?php

// 2019.08.23 bY Stefano.Deiuri@Elettra.Eu

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

define( 'CFG_VERSION', 1 );

if (!$cfg =config( 'page_po_status', true )) {
	echo json_encode(array( 'error' => true ));
	die;
}
	
$ret =array(
	'cfg' =>array(
		'version' =>CFG_VERSION
		)
	);
	
$editors =file_read_json( $cfg['in_editors'], true );
$papers =file_read_json( $cfg['in_edot'], true );
$stats_db =file_read_json( $cfg['in_stats'], true );

$ts_rqst =$_GET['ts'];


/* 
ksort($dots);

foreach ($dots as $paper_id =>$p) {
	if (($p['pc'] == 'Y' || $p['status'] == 'removed') && $p['ts'] > $ts_rqst) {
		$status =$p['status'];
		if ($status == 'nofiles') $status ='';
		$class =($p['qaok'] ? 'qaok' : $status);				
		$ret['edots'][$paper_id] =$class;
	}
}
*/

foreach ($papers as $pcode =>$p) {
	if (empty($p['status_ts']) || $p['status_ts'] > $ts_rqst) {
		$status =$p['status'];
		if (empty($status) || $status == 'nofiles') $status ='';
		$class =($p['qa_ok'] ? 'qaok' : $status);				
		$ret['edots'][$pcode] =$class;
	}
}
	
if (!$ts_rqst) {
	$ret['cfg'] =array(
		'version' =>CFG_VERSION,
		'conf_name' =>CONF_NAME,
		'change_page_delay' =>10, // seconds
		'reload_data_delay' =>30, // seconds	
		'history_date_start' =>APP_HISTORY_DATE_START
		);
		
		
	$ret['history'] =$stats_db;

} else {
	foreach ($stats_db as $tm2 =>$x) {
		if ($x['ts'] > $ts_rqst) $ret['history'][$tm2] =$x;
	}	
}	

$ret['editors'] =$editors;

$ret['ts'] =time();
//$ret['ts'] = 0;

$ret['colors'] =array();
foreach (array( 'files', 'a', 'qaok', 'g', 'y', 'r', 'nofiles' ) as $cname) {
	$ret['colors'][] =$cfg['colors'][$cname];
}

$ret['labels'] =$cfg['labels'];

if ($_GET['debug']) {
	echo "<pre>";
	$cfg['spms_passphrase'] ='****';
	print_r( $cfg );
	print_r( $ret );
	print_r( $dots );
	return;
}

gz_http_response( json_encode( $ret ) );

?>
