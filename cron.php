#!/usr/bin/php
<?php

/* bY Stefano.Deiuri@Elettra.Eu

2023.12.29 - add new cfg block, as others scripts
2023.05.04 - update logs
2022.08.22 - update

*/

require( 'config.php' );
require_lib( 'jict', '1.0' );

if (ROOT_PATH == '.' || ROOT_PATH == '') {
	echo "\n\nWrong configuration! Please check config.php!\n\n\n";
	die;
}

$cfg =config( 'cron' );
$cfg['force'] =false;

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-f':
		case '-force':
			$cfg['force'] =true;
			break;

		case '-v':
			$cfg['verbose'] ++;
			break;
	}
}

if ($cfg['force']) echo "force = $cfg[force]\n\n";

if (!$cfg['enabled'] && $force == false) {
	echo "\n\nCron disabled!\n\nCheck \$cws_config[cron][enabled] in config.php\n\n";
	die;
}

$run_h =date('H');
$run_m =date('i');

$rs =0;

$cron_log =$cfg['logs_path'] ."/cron.log";

foreach ($cws_config as $app =>$config) {
	$run =$cfg['force'];
	
	if (!empty($config['cron']) && file_exists( ROOT_PATH ."/$app/make.php" )) {
		if (strpos( $config['cron'], ':' )) list( $h, $m ) =explode( ':', $config['cron'] );
        else $h =$m =false;
		
		if ($h == '*' && $m == '*') $run =true; // *:*
		else if ($h == '*' && substr( $m, 0, 1 ) == '*' && (int)$run_m%(int)substr( $m, 1 ) == 0) $run =true; // *:*mm every mm minutes
		else if ($h == '*' && $m == $run_m) $run =true;	// *:i
		else if ($h == $run_h && $m == $run_m) $run =true; // H:i

		if ($run) {
			$rs ++;

			echo "\n" .date('r') ." ------------------------------------------------------------------------------\n";

			//$app_log =sprintf( "%s/logs/%s.log", ROOT_PATH, $app );
			$app_log =sprintf( "%s/%s.log", $cfg['logs_path'], $app );

			$t0 =time();
			$cmd ="cd " .ROOT_PATH ."/$app; ./make.php" 
				.(empty($config['cron_options']) ? false : ' ' .$config['cron_options'])
				." >$app_log 2>&1; cat $app_log";

			echo "Run $app at $h:$m\n";
			system( $cmd );

			$exec_sec =time()-$t0;

			echo "\nexecution time: $exec_sec sec\n\n";

			file_write( $cron_log, date('r') ." - $rs - $app - $exec_sec sec\n", 'a' );

		} else {
			if ($cfg['verbose'] > 2) echo "$app schedulet at $h:$m\n";
		} 
	}
}

//if ($rs) file_write( ROOT_PATH ."/logs/cron.log", "\n", 'a' );
if ($rs) file_write( $cron_log, "\n", 'a' );

?>