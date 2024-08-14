<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.08.29 - update
2022.07.25 - update

*/

require( '../config.php' );
require_lib( 'jict','1.0' );

$cfg =config( 'app_paper_status' );

//print_r( $cfg );

ini_set( 'display_errors', 0 ) ;

//date_default_timezone_set( CWS_TIMEZONE );

$on_load =false;
$message =false;
$page =false;

$primary_color =COLOR_PRIMARY;

if (!need_file( APP_IN_PAPERS )) {
	echo_error( "\n\nTry to run indico_stats_importer/make.php!" );
	die;
}


	
$pc =strtoupper(trim($_GET['paper_code'] ?? false));
if ($pc) {
	$papers =file_read_json( APP_IN_PAPERS, true );
	
	if (!empty($papers[$pc])) {
		$title ="$pc Paper Status";

		$status_code =$papers[$pc]['status'];
		if (empty($status_code)) $status_code='nofiles';
		
		$primary_color =$cfg['colors'][$status_code];
		
		$message =$cfg['labels'][$status_code];
				
		$page ="
<div id='status_msg' class='b_$status_code'>
$message
</div>
<div id='status'>
$pc
<br />
<br />
<b>" .$papers[$pc]['title'] ."</b>

<hr noshade size='1' />
<small>page loaded at " .date( 'Y-m-d H:i (O)' ) ."</small>
<input type='button' id='refresh' onClick='location.reload(true);' value='Refresh' />

</div>
";
		file_write( APP_LOG, date('U') ."\t$pc\t$status_code\n", 'a' );
		
	} else {
		$message ="<b style='color: red;'>Paper not found</b><br /><br />";
	}
}
	
if (!$page) {
	$title =$cfg['conf_name'] ." Paper Status";
	$page ="
<center>
<h1>" .$cfg['conf_name'] ."</h1>
$message
<form>
PAPER CODE
<br />
<input type='text' name='paper_code' id='paper_code' />
<br />
<input type='submit' value='SEARCH' />
</form>
</center>
";

	$on_load ="onLoad=\"document.getElementById('paper_code').focus();\"";
}


$T =new TMPL( $cfg['template'] );

$T->set([
	'title' =>$title,
	'content' =>$page,
	'on_load' =>$on_load,
	'primary_color' =>$primary_color
	]);

echo $T->get();

?>