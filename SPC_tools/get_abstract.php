<?php

/* Created by Nicolas.Delerue@ijclab.in2p3.fr
2025.11.19 1st version

This gets an abstract passed by ID in a POST request (necessary to avoid CORS problems).

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


if (count($_POST)==0){
    if (count($_GET)>0){
        $abstract_id= $_GET['abstract_id'];
    } else {
         //echo "No arguments passed, using defaults\n";   
         $abstract_id=109;
        die("No arguments passed, exiting\n");
    }    
} else {
    $abstract_id= $_POST['abstract_id'];
}

//echo "abstract_id $abstract_id \n";


$post_data=array( "abstract_id" => $abstract_id );
$base_url="/event/".$cws_config['global']['indico_event_id']."/manage/abstracts/abstracts.json";
//echo "base_url $base_url \n";
$req =$Indico->request( $base_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true));
//var_dump($post_data);
echo json_encode($req);

//post_data="track-"+track_id+"-csrf_token="+token+"&track-"+track_id+"-question_67=1&track-"+track_id+"-question_68=0&track-"+track_id+"-contribution_type=__None&track-"+track_id+"-proposed_action=accept&track-"+track_id+"-comment=";

?>