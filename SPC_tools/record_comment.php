<?php

/* by Nicolas.Delerue@ijclab.in2p3.fr

2025.11.20 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'SPC_tools' );
$cfg['verbose'] =1;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

echo("auth OK ");
//echo var_dump($user);
echo "<BR/>here<BR/>"; 

$Indico->load();

echo("indico loaded<BR/>");

//$req =$Indico->request( "/event/{id}/manage/abstracts/abstracts.json", 'GET', false, array( 'return_data' =>true, 'quiet' =>true ) );

/*
foreach ($req["abstracts"] as $abs) {
    echo "<A HREF='http://indico.jacow.org/event/".$cfg['indico_event_id']."/abstracts/".$abs["id"]."'>".$abs["id"]."</A>:&nbsp;".$abs["friendly_id"].":&nbsp;".$abs["title"]."<BR/>";
}
*/


if (count($_POST)==0){
    //die("No POST arguments passed, exiting\n");
    if ($code_testing==1) {
         echo "No POST arguments passed.\n";   
    }
     // abstract_id=113&vote_value=1&review_id=0&track_id=47
    if (count($_GET)>0){
        if ($code_testing==1) {
            echo "Using GET data\n";   
        }
        $abstract_id= $_GET['abstract_id'];
        $comment= $_GET['comment'];
    } else {
        if ($code_testing==1) {
            echo "No arguments passed, using defaults\n";
        }
        $abstract_id=125;
        $comment= "test";
    }
} else {
    $abstract_id= $_POST['abstract_id'];
    $comment= $_POST['comment'];
}

$req =$Indico->request( "/event/{id}/abstracts/".$abstract_id."/comment", 'POST', array( 'text' => $comment , 'visibility' => "reviewers" ) , array( 'return_data' =>true, 'quiet' =>true, 'use_session_token' => true ) );
var_dump($req);

?>