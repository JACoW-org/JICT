#!/usr/bin/php
<?php

/* bY Stefano.Deiuri@Elettra.Eu

2023.05.04 - update logs
2022.08.22 - update

*/

require( 'config.php' );
//require_lib( 'cws','1.0' );
//require_once( 'conference-config.php' );

if (ROOT_PATH == '.' || ROOT_PATH == '') {
	echo "\n\nWrong configuration! Please check config.php!\n\n\n";
	die;
}

$cfg =array(
	'force' =>false
	);

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-f':
		case '-force':
			$cfg['force'] =true;
			break;
	}
}

if ($cfg['force']) echo "force = $cfg[force]\n\n";

if (!$cws_config['global']['cron_enabled'] && $force == false) {
	echo "\n\nCron disabled!\n\nCheck \$cws_config[global][cron_enabled] in config.php\n\n";
	die;
}

$mode =strtolower($cws_config['global']['mode']);

$run_h =date('H');
$run_m =date('i');

foreach ($cws_config as $app =>$config) {
	$run =$cfg['force'];
	
	if (!empty($config['cron']) && file_exists( ROOT_PATH ."/$app/make.php" )) {
		if (strpos( $config['cron'], ':' )) list( $h, $m ) =explode( ':', $config['cron'] );
        else $h =$m =false;
		
		if ($h == '*' && $m == '*') $run =true; // *:*
		else if ($h == '*' && substr( $m, 0, 1 ) == '*' && (int)$run_m%(int)substr( $m, 1 ) == 0) $run =true; // *:*mm every mm minutes
		else if ($h == '*' && $m == $run_m) $run =true;	// *:i
		else if ($h == $run_h && $m == $run_m) $run =true; // H:i

		if ($mode == 'indico' && substr($app, 0, 5) == 'spms_') $run =false;
		else if ($mode == 'spms' && substr($app, 0, 7) == 'indico_') $run =false;
				
		if ($run) {
			echo "\n" .date('r') ." ------------------------------------------------------------------------------\n";

/*  			if (!(fileperms( "$app/make.php" ) & 0x0008)) {
				echo "Change file permission to 0755.\n";
				chmod( "$app/make.php", 0755 );
			}  */

			$t0 =time();
			$cmd ="cd " .ROOT_PATH ."/$app; ./make.php" .(empty($config['cron_options']) ? false : ' ' .$config['cron_options']);
			echo "Run $cmd at $h:$m\n";
			system( $cmd );

			$exec_sec =time()-$t0;

			echo "\nexecution time: $exec_sec sec\n\n";

			file_write( ROOT_PATH ."/logs/cron.log", date('r') ." - $app - $exec_sec sec\n", 'a' );

		} else {
			//file_write( ROOT_PATH ."/logs/cron.log", date('r') ." - SKIP $app (schedule $h:$m) (now $run_h:$run_m)\n", 'a' );
			//echo "$app schedulet at $h:$m\n";
			//var_dump( $h, $m, $run );
		} 

//	} else if ($app != 'global') {
//		echo "$app disabled!\n";
	}
}


//----------------------------------------------------------------------------
function file_write( $_filename, $_data, $_mode ='w', $_verbose =false, $_verbose_message =false ) {
	
    if ($_verbose)	echo "Save $_verbose_message ($_filename)... ";
        
    $fp =fopen( $_filename, $_mode );

    if (!$fp) {
    //	echo "unable to save file $_filename!\n";
        if ($_verbose)echo_error( "ERROR (writing)" );
        return false;
    }

    fwrite( $fp, (is_array($_data) ? implode('',$_data) : $_data) );

    fclose( $fp );

    if ($_verbose)	echo_ok();

    return true;
}

?>
