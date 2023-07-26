<?php

/* bY Stefano.Deiuri@Elettra.Eu

2023.03.31 - update
2022.08.29 - update

*/

require( 'config.php' );

require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'global' );

$Indico =new INDICO( $cfg );

session_start();

$user =$Indico->auth();
if (!$user) exit;

$T =new TMPL( 'template.html' );
$T->set([
    'style' =>'main { font-size: 22px; } main ul { margin: 20px; }',
    'title' =>'JACoW Conference Website Scripts',
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ) ." " .__h( 'i', "", [ 'class' =>'fa fa-power-off', 'onClick' =>"document.location =\"$_SERVER[PHP_SELF]?cmd=logout\"" ]),
    'path' =>'./',
    'scripts' =>false,
    'js' =>false
    ]);


if (ROOT_PATH == '.' || ROOT_PATH == '') {
	echo "Wrong configuration! Please check config.php!";
	die;
}

$links =false;
foreach ($cws_config as $app =>$x) {
	if (isset($x['out_html'])) {
		$href ="html/" .str_replace( '{out_path}/', "", $x['out_html'] );

		if (file_exists($href)) $links[$x['name']] ="<a href='$href' target='_blank'>$x[name]</a>";
		else $links[$x['name']] ="$x[name]" .(substr($app,0,4) == 'make' ? "<br /><small>(run $app/make.php [$href])</small>" : false);
		
	} else if (isset($x['default_page']) && (empty($x['only_me']) || me())) {
		$href =str_replace( '{app}', $app, $x['default_page'] );

		if (file_exists($href)) $links[$x['name']] ="<a href='$href' target='_blank'>$x[name]</a>";
		else $links[$x['name']] ="$x[name]" .(substr($app,0,4) == 'make' ? "<br /><small>(run $app/make.php)</small>" : false);

		if (!empty($x['allow_roles'])) $links[$x['name']] .=sprintf( ' <i class="fa-solid fa-lock" title="roles allowed: %s"></i>', implode( ',', $x['allow_roles'] ));
	}
}

ksort( $links );

$gcfg =$cws_config['global'];

$logo =file_exists( $gcfg['logo'] ) ? "<img src='$gcfg[logo]' style='border:0; width:200px;' />" : $gcfg['conf_name'];
$logo2 =sprintf( "<a href='%s' target='blank'>%s</a>", $gcfg['conf_url'], $logo );

$ds =explode( ' ', date( 'Y M j', strtotime( $cws_config['global']['date_start'] )));
$de =explode( ' ', date( 'Y M j', strtotime( $cws_config['global']['date_end'] )));

if ($ds[1] == $de[1]) $dates ="$ds[2] - $de[2] $ds[1] $ds[0]";
else $dates ="$ds[2]/$ds[1] - $de[2]/$de[1], $ds[0]";

if (!empty( $cws_config['global']['location'] )) $dates =$cws_config['global']['location'] ."<br />" .$dates;


$T->set( 'content',
    __h( "div",
        __h( "div", __h( 'ul', "<li>" .implode( "</li>\n<li>", $links ) ."</li>\n" ), [ "class" =>"col-md-6" ])
        .__h( "div", "<br />$logo2<br />$dates", [ "class" =>"col-md-6 text-right" ]), 
        [ 'class' =>'row' ])
    );

echo $T->get();

?>