<?php

// 2019.04.30 bY Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'jict','1.0' );

config( 'barcode' );

if (!need_file( APP_IN_PAPERS )) {
	echo_error( "\n\nTry to run indico_importer/make.php!" );
	die;
}

$paper_id =_G('paper_id');

$script_url ="https://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]";

switch (_G('cmd')) {
	default:
		$client =$_SERVER['REMOTE_ADDR'];
		$pair_code =md5( $client .time() );
		$actions_list ='Open,QA,Search,AuthorMaintenance,Test';
		$qrcode_content ="$script_url|$client|$pair_code|$actions_list";
		
		$client2 =niceip( $client, '_' );
		$png_fname =$client2 .'-qrcode.png';
		$qrcode_png =APP_QRCODE_PATH .'/' .$png_fname;
		$qrcode_url =APP_QRCODE_URL .'/' .$png_fname;
		
		require( '../libs/phpqrcode/qrlib.php' );
		QRcode::png( $qrcode_content, $qrcode_png, 'L', 4 );
		
		$client_obj =array(
			'client' =>$client,
			'pair_code' =>$pair_code,
			'ts' =>time()
			);
			
		save_client_obj( $client, $client_obj, true );

		page( 'Pair Code', "<center><br /><br /><b>Pair Code<br /><br /><img src='$qrcode_url' />"
			."<br /><br /><a href='index.php?cmd=download'>Download App</a>"
			."<br /><br /><a href='index.php?cmd=get'>Paper URL</a>"
			."<!-- $qrcode_content -->\n" );
			
		return;
		
	case 'pair_confirm':
		$client_obj =read_client_obj( _G('client'), true );
		
		if (!$client_obj) {
			echo "Please refresh Pair Code and scan again!";
			return;
		}
		
		if (_G('pair_code') == $client_obj['pair_code']) {
			pair_ok();
			echo "ok";
			log_activity( 'pair' );
		} else {
			echo "error: Please refresh Pair Code and scan again!";
		}
		
		return;
	
	case 'download':
		$pair_code =md5( time() );
		$qrcode_content =str_replace( 'index.php', APP_APK, $script_url );
		$qrcode_png =APP_QRCODE_PATH .'/qrcode_apk.png';
		$qrcode_url =APP_QRCODE_URL .'/qrcode_apk.png';
		
		require( '../libs/phpqrcode/qrlib.php' );
		QRcode::png( $qrcode_content, $qrcode_png, 'L', 4 );
		
		page( 'Download Address', "<center><br /><br /><b>Download address<br /><br /><img src='$qrcode_url' /><!-- $qrcode_content -->\n" );
		return;

	case 'set':
		$client_obj =read_client_obj( _G('client') );
		
		if ($client_obj && _G('pair_code') == $client_obj['pair_code'] && $paper_id) {
			$db =file_read_json( APP_IN_PAPERS, true );
			if (isset($db[$paper_id]['abstract_id'])) {
				$action =_G('action');
				$client_obj['paper_id'] =$paper_id;
				$client_obj['abstract_id'] =$db[$paper_id]['abstract_id'];
				$client_obj['action'] =$action;
				save_client_obj( _G('client'), $client_obj );
				echo "$action $paper_id";
			
				log_activity( 'set', $paper_id .' ' .$action );
			} else {
				echo "Wrong paper code!";
			}
		} else {
			echo "Wrong paper code!";
		}
		return;
		
	case 'get':
		$client_obj =read_client_obj( $_SERVER['REMOTE_ADDR'] );
		if ($client_obj) {
			$db =file_read_json( APP_IN_PAPERS, true );
			
			$paper_id =$client_obj['paper_id'];
			$abs_id =$db[$paper_id]['abstract_id'];

			log_activity( 'get', $paper_id .' ' .$client_obj['action'] );
			
			$base_url =SPMS_URL;
			
			switch ($client_obj['action']) {
				case 'Search':
					header( "Location:$base_url/!search.results?PID=$paper_id&display=dummy&display=CO&display=AT&display=FA&display=PRET&display=MC&display=SC" );
					break;
				case 'AuthorMaintenance':
					header( "Location:$base_url/abstract_maint.edit?ID=$abs_id" );
					break;
				case 'QA':
					header( "Location:$base_url/editor.qa_paper?abs_id=$abs_id" );
					break;
				default:
					header( "Location:$base_url/editor.process_paper?abs_id=$abs_id" );
			}
		} else {
			echo "Please scan a paper!";
		}
		return;
}

//-----------------------------------------------------------------------------
function pair_ok() {
 $client2 =niceip( _G('client'), '_' );
 $client_obj_tmp_fname =APP_DATA_PATH .'/_' .$client2 .'.json';
 $client_obj_fname =APP_DATA_PATH .'/' .$client2 .'.json';
 rename( $client_obj_tmp_fname, $client_obj_fname );
}

//-----------------------------------------------------------------------------
function save_client_obj( $_client, $_obj, $_tmp =false ) {
 $client2 =niceip( $_client, '_' );
 $client_obj_fname =APP_DATA_PATH .'/' .($_tmp ? '_' : false) .$client2 .'.json';
 return file_write_json( $client_obj_fname, $_obj );	
}

//-----------------------------------------------------------------------------
function read_client_obj( $_client, $_tmp =false ) {
 $client2 =niceip( $_client, '_' );
 $client_obj_fname =APP_DATA_PATH ."/" .($_tmp ? '_' : false) .$client2 .'.json';
 
 if (!file_exists( $client_obj_fname )) return false;
 
 return file_read_json( $client_obj_fname, true );	
}

//-----------------------------------------------------------------------------
function log_activity( $_cmd, $_comment =false ) {
 file_write( APP_LOG, date('U') ."\t$_cmd\t$_comment\n", 'a' );	
}

//-----------------------------------------------------------------------------
function niceip( $_ip, $_sep ='.' ) {
 if (strpos( $_ip, ',' )) $_ip =substr( $ip, 0, strpos( $_ip, ',' ));
 $x =explode( '.', $_ip );
 return str_pad( $x[0], 3, '0', STR_PAD_LEFT ) .$_sep .str_pad( $x[1], 3, '0', STR_PAD_LEFT ) .$_sep .str_pad( $x[2], 3, '0', STR_PAD_LEFT ) .($x[3] ? $_sep .str_pad( $x[3], 3, '0', STR_PAD_LEFT ) : false);
}

//-----------------------------------------------------------------------------
function page( $title, $page ) {
 $tmpl =implode( '', file( APP_TEMPLATE_HTML ));
 foreach (array( 'title', 'on_load', 'page' ) as $var) {
	$tmpl =str_replace( '{'.$var.'}', $$var, $tmpl );
 }
 echo $tmpl;	
}

?>
