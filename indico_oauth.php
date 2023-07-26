<?php

/* bY Stefano.Deiuri@Elettra.Eu

2023.03.31 - 1st version

*/

require( 'config.php' );

require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

session_start();

$cfg =config( 'global' );

$Indico =new INDICO( $cfg );


//require( 'y75.lib.php' );
//require( 'local/jacow-indico.include.php' );

if (empty($_GET['code'])) {
    $Indico->oauth( 'error', 'no code' );
	exit;
}
    
$Indico->oauth( 'token' );
header( 'Location: /cws/index.php' );

?>