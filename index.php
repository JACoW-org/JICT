<?php

/* bY Stefano.Deiuri@Elettra.Eu

2023.11.27 - handle public access mode
2023.03.31 - update
2022.08.29 - update

*/

require( 'config.php' );

require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'global' );

$Indico =new INDICO( $cfg );

//session_start();

$user =$Indico->auth();
if (!$user) exit;

$T =new TMPL( 'template.html' );
$T->set([
    'style' =>'main { font-size: 22px; } main ul { margin: 20px; }',
    'title' =>'JACoW-Indico Conference Tools',
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ) ." " .(empty($user['public']) ? __h( 'i', "", [ 'class' =>'fa fa-power-off', 'onClick' =>"document.location =\"$_SERVER[PHP_SELF]?cmd=logout\"" ]) : false),
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
    if (empty($x['hide'])) {
        if (isset($x['out_html'])) {
            $href ="html/" .str_replace( '{out_path}/', "", $x['out_html'] );
    
            if (file_exists($href)) $links[$x['name']] ="<a href='$href' target='_blank'>$x[name]</a>";
            else $links[$x['name']] ="$x[name]" .(substr($app,0,4) == 'make' ? "<br /><small>(run $app/make.php [$href])</small>" : false);
            
        } else if (isset($x['default_page']) && (empty($x['only_me']) || me())) {
            $href =str_replace( '{app}', $app, $x['default_page'] );
    
            if (file_exists($href)) $links[$x['name']] ="<a href='$href' target='_blank'>$x[name]</a>";
            else $links[$x['name']] ="$x[name]" .(substr($app,0,4) == 'make' ? "<br /><small>(run $app/make.php)</small>" : false);
    
            if (!empty($x['allow_roles']) && empty($user['public'])) $links[$x['name']] .=sprintf( ' <i class="fa-solid fa-lock" title="roles allowed: %s"></i>', implode( ',', $x['allow_roles'] ));
            
            if (!empty($x['export_data']) && me()) $links[$x['name']] .=sprintf( ' <a href="%s?export_data=yes" target="_blank"><i class="fa-solid fa-file-export" title="export data"></i></a>', $href );
        }
    }
}

ksort( $links );

$gcfg =$cws_config['global'];

$logo =file_exists( $gcfg['logo'] ) ? "<img src='$gcfg[logo]' style='border:0; width:200px;' /><br />" : false;
$logo2 =sprintf( "<a href='%s' target='blank'>%s%s</a>", $gcfg['conf_url'], $logo, substr( $gcfg['conf_url'], 8 ) );

$dates_c =$gcfg['dates']['conference'];

$ds =explode( ' ', date( 'Y M j', strtotime( $dates_c['from'] )));
$de =explode( ' ', date( 'Y M j', strtotime( $dates_c['to'] )));

if ($ds[1] == $de[1]) $dates ="$ds[2] - $de[2] $ds[1] $ds[0]";
else $dates ="$ds[2]/$ds[1] - $de[2]/$de[1], $ds[0]";

if (!empty( $gcfg['location'] )) $dates =$gcfg['location'] ."<br />" .$dates;

$dates .=sprintf( "<br /><a href='%s/event/%d/manage' target='_blank'>Indico</a>", $gcfg['indico_server_url'], $gcfg['indico_event_id'] );

$T->set( 'content',
    __h( "div",
        __h( "div", __h( 'ul', "<li>" .implode( "</li>\n<li>", $links ) ."</li>\n" ), [ "class" =>"col-md-6" ])
        .__h( "div", "<br />$logo2<br />$dates", [ "class" =>"col-md-6 text-right" ]), 
        [ 'class' =>'row' ])
    );

echo $T->get();

?>