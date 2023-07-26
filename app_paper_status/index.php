<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.08.29 - update
2022.07.25 - update

*/

require( '../config.php' );
require_lib( 'cws','1.0' );

$cfg =config( 'app_paper_status' );

ini_set( 'display_errors', 0 ) ;

date_default_timezone_set( CWS_TIMEZONE );

$on_load =false;
$message =false;
$page =false;

$primary_color =COLOR_PRIMARY;

$mode =strtolower($cfg['mode']) == 'indico' ? 'indico' : 'spms';

if (!need_file( APP_IN_PAPERS )) {
	echo_error( "\n\nTry to run ${mode}_stats_importer/make.php!" );
	die;
}


	
$pc =strtoupper(trim(_G('paper_code')));
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
	$title =CONF_NAME ." Paper Status";
	$page ="
<center>
<h1>".CONF_NAME."</h1>
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


$tmpl =implode( '', file( APP_IN_TEMPLATE_HTML ));
foreach (array( 'title', 'on_load', 'page', 'primary_color' ) as $var) {
	$tmpl =str_replace( '{'.$var.'}', $$var, $tmpl );
}
echo $tmpl;

?>
