#!/usr/bin/php
<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.08.02 - update for Indico

*/

if (in_array( '--help', $argv )) {
	echo "Program options:\n"
		."\n";
	return;
}

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config();

$Indico =new INDICO( $cfg );
$Indico->import();

$countries =[];
$participants =[];

foreach ($Indico->data['registrants']['registrants'] as $rid =>$r) {
    $participants[$r['type']]["$r[surname] $r[name]"] ="<b>$r[surname] $r[name]</b> ($r[inst]" .(empty($r['nation']) ? false : ", $r[nation]") .")";

    if (empty($countries[$r['nation']])) $countries[$r['nation']] =1;
    else $countries[$r['nation']] ++;

    $date =date( 'Y-m-d', $r['ts'] );
    if (empty($chart[$date][0])) $chart[$date][0] =1;
    else $chart[$date][0] ++;
}

ksort( $chart );

$Indico->GoogleChart( $chart );

print_r( $Indico->data['registrants']['stats'] );


$D =&$participants['D'];
ksort( $D );

$delegates_n =count( $D );
$delegates_list =implode( "<br />\n", $D );

$S =&$participants['S'];
if (!empty($S) && count($S)) {
    ksort( $S );
    $exhibitors_n =count( $S );
    $exhibitors_list =implode( "<br />\n", $S );

} else {
    $exhibitors_n =0;
}


// countries chart

arsort( $countries );
$countries_n =count( $countries );
$countries_list ="<table class='participants_countries'>";

$f =false;
foreach ($countries as $name =>$num) {
	if (!$f) $f =350/$num;
	if ($name && $name != 'Unknown') $countries_list .="<tr><th>$name</th><td vliagn='middle'><div class='chart_bar' style='width: " .round($num *$f, 0) ."px;'></div> $num</td></tr>\n";
}
$countries_list .="</table>";


// save files

$width  =CHART_WIDTH;
$color1 =$cfg['colors']['primary'];
$color2 =$cfg['colors']['secondary'];
$js =APP_OUT_JS;

$chart_var =$cfg['chart_var'];

$fname =$exhibitors_n ? $cfg['template_html'] : str_replace( '.html', '-without-exhibitors.html', $cfg['template_html'] );
$template =file_read( $fname, true, "template" );
eval( "\$out =\"$template\";" );
file_write( OUT_PATH .'/' .$cfg['out_html'], $out, 'w', true, 'html' );

echo "\n";
$template =file_read( $cfg['css'], true, "template" );
eval( "\$out =\"$template\";" );
file_write( OUT_PATH .'/' .$cfg['out_css'], $out, 'w', true, 'css' );

?>