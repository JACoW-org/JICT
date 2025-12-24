<?php

/* Created by Nicolas.Delerue@ijclab.in2p3.fr
2025.11.12 1st version

This page gives links to several tools needed by SPC.

*/
if (str_contains($_SERVER["QUERY_STRING"],"debug")){
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} //if debug on

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'SPC_tools', false, false );
$cfg['verbose'] =0;

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

$content .="<A HREF=\"vote.php?text=0\">Page to vote for abstracts (without full text).</A><BR/>\n";
$content .="<A HREF=\"vote.php?text=1\">Page to vote for abstracts (with full text).</A><BR/>\n";
$content .="<A HREF=\"myvotes.php\">Page listing the abstracts you voted for.</A><BR/>\n";
$content .="<A HREF=\"count_votes.php\">Page counting the votes for all SPC members.</A><BR/>\n";

$T->set( 'content', $content );

$T->set( 'done_n', 0 );

echo $T->get();

?>
