#!/usr/bin/php
<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.12.14 - update
2018.04.13 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );

$cfg =config( 'make_colors_css' );

//print_r( $cfg ); return;

$dark_threshold =200;

$css =false;
foreach ($cfg['colors'] as $color_name =>$color) {
//    $color_name =substr( $var, 6 );
    
    $r =hexdec(substr( $color, 1, 2 ));
    $g =hexdec(substr( $color, 3, 2 ));
    $b =hexdec(substr( $color, 4, 2 ));
    
    $text_color =($r < $dark_threshold && $g < $dark_threshold && $b < $dark_threshold) ? 'white' : 'black';
    
    $css .="
/* ($r,$g,$b) */
.$color_name {
	color: $color; 
}

.b_$color_name {
	background-color: $color !important;
	color: $text_color !important;
}

.b_$color_name a {
	color: $text_color;
}


    ";			
}

$fname =APP_OUT_CSS;
echo "Write $fname... ";
echo_result( file_write( $fname, $css ) );

/* 
foreach ($cws_config as $app =>$config) {
	if (isset($config['colors_css']) && $config['colors_css']) {
		$fname ="../$app/colors.css";
		echo "Write $fname... ";
		echo_result( file_write( $fname, $css ) );
	}
}
*/

?>