<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
<?php

/* by Nicolas.Delerue@ijclab.in2p3.fr
2025.11.12 1st version

This page gives links to several tools needed by SPC.

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'SPC_tools', false, false );
$cfg['verbose'] =1;


$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

$Indico->load();



$T =new TMPL( $cfg['template'] );
$T->set([
    'style' =>'main { font-size: 22px; } main ul { margin: 20px; }',
    'title' =>$cfg['name'],
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['full_name'] ),
    'path' =>'../',
    'head' =>"<link rel='stylesheet' type='text/css' href='../page_edots/colors.css' />
    <link rel='stylesheet' type='text/css' href='style.css' />",
    'scripts' =>"",
    'js' =>false
    ]);


$content ="";
$content .="x11<BR/>\n";

$content .="<A HREF=\"vote.php\">Page to vote for abstracts</A>\n";

$T->set( 'content', $content );

$T->set( 'done_n', 0 );

echo $T->get();

?>
x13