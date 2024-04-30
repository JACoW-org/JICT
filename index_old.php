<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.08.29 - update

*/

require( 'config.php' );

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

$logo =file_exists( $gcfg['logo'] ) ? "<img src='$gcfg[logo]' style='float:right; border:0; max-width:200px' />" : $gcfg['conf_name'];

$ds =explode( ' ', date( 'Y M j', strtotime( $cws_config['global']['date_start'] )));
$de =explode( ' ', date( 'Y M j', strtotime( $cws_config['global']['date_end'] )));

if ($ds[1] == $de[1]) $dates ="$ds[2] - $de[2] $ds[1] $ds[0]";
else $dates ="$ds[2]/$ds[1] - $de[2]/$de[1], $ds[0]";

if (!empty( $cws_config['global']['location'] )) $dates =$cws_config['global']['location'] .' > ' .$dates;

?>
<html>
<head>
	<title><?php echo $cws_config['global']['conf_name']; ?> CWS</title>

	<link href='https://fonts.googleapis.com/css?family=Lato:400,300' rel='stylesheet' type='text/css'>
	<link rel='stylesheet' href='dist/fontawesome-free-6.3.0-web/css/all.css' type='text/css' />

	<link href='<?php echo $gcfg['logo']; ?>' rel='SHORTCUT ICON' />
	
	<style>
	body {
		background: #fff;
		margin: 10px;
		font-family: 'Lato', Arial;
		font-size: 20px;
		font-weight: 300;
		}
		
	h1, li {
		margin-bottom: 20px;
		}
	</style>
</head>

<body>
<a href='<?php echo $cws_config['global']['conf_url']; ?>' target='_blank'><?php echo $logo; ?></a>
<h1>JACoW Conference Website Scripts for <?php echo $cws_config['global']['conf_name']; ?></h1>
<h4><?php echo $dates; ?></h4>
<ul>
<?php
echo "<li>" .implode( "</li>\n<li>", $links ) ."</li>\n";
?>
</ul>
</body>
</html>
