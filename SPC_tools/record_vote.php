<?php

/* Created by Nicolas.Delerue@ijclab.in2p3.fr
2025.11.17 1st version

This page records the vote on a given abstract passed by ID in a POST request.

*/
$code_testing=1;

if (str_contains($_SERVER["QUERY_STRING"],"debug")){
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $code_testing=1;
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

require( 'autoconfig.php' );


$first_question_id=$cws_config['SPC_tools']['first_question_id'];
$second_question_id=$cws_config['SPC_tools']['second_question_id'];

print("first_question_id ".$first_question_id);

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
        $vote_value= $_GET['vote_value'];
        $review_id= $_GET['review_id'];
        $track_id= $_GET['track_id'];
        $new_track_id= $_GET['new_track_id'];
        //$action= $_GET['action'];
        $csrf_token= $_GET['csrf_token'];
    } else {
        if ($code_testing==1) {
            echo "No arguments passed, using defaults\n";
        }
        if (1==0){
            $abstract_id=109;
            $vote_value= 1;
            $review_id=16835;
            $track_id=47;
            $track_id=83;
        } else {
            $abstract_id=125;
            $vote_value= 2;
            $review_id=0;
            $track_id=85;
        }
    }
} else {
    $abstract_id= $_POST['abstract_id'];
    $vote_value= $_POST['vote_value'];
    $review_id= $_POST['review_id'];
    $track_id= $_POST['track_id'];
    $new_track_id= $_POST['new_track_id'];
    //$action= $_POST['action'];
    $csrf_token= $_POST['csrf_token'];
}
if ($code_testing==1) {
    echo "abstract_id $abstract_id \n";
    echo "vote_value $vote_value \n";
    echo "review_id $review_id \n";
    echo "track_id $track_id \n";
    echo "csrf_token $csrf_token \n";    
}

//syntax found using https://indico.jacow.org/event/37/abstracts/109/reviews/16835/edit
if ($vote_value==1){
    $question1=1;
    $question2=0;
} else if ($vote_value==2) {
    $question1=0;
    $question2=1;
} else {
    $question1=0;
    $question2=0;
}

$post_data=array();
// "track" => $track_id , 
$post_data["track-".$track_id."-question_".$first_question_id] = $question1;
$post_data["track-".$track_id."-question_".$second_question_id] =  $question2;
if ($new_track_id>0){
$post_data["track-".$track_id."-proposed_tracks"] = $new_track_id;
$post_data["track-".$track_id."-proposed_action"]= "change_tracks";
} else {
    $post_data["track-".$track_id."-proposed_action"]= "accept";
}

if ($review_id>0){
    //$post_data=array( "track" => $track_id , "track-".$track_id."-question_$first_question_id" => $question1, "track-".$track_id."-question_$second_question_id" =>  $question2 );
    //$post_data=array( "track-".$track_id."-csrf_token" => $csrf_token,  "track" => $track_id , "track-".$track_id."-question_$first_question_id" => $question1, "track-".$track_id."-question_$second_question_id" =>  $question2 , "track-".$track_id."-proposed_action" => "accept" , "track-".$track_id."-proposed_contribution_type" => "23" , "track-".$track_id."-comment" => "None...");
    //$post_data=array( 'track-'.$track_id.'-comment' => 'from PHP track 47' , "track" => $track_id , "track-".$track_id."-question_20" => 0 , "track-".$track_id."-question_19" => 0, "track-".$track_id."-proposed_action" => "accept");
    //$post_data=array( 'comment' => "test1711_1617r" , 'visibility' => "conveners", "track" => 47 , "question_1" => "1" , "question_2" => "1" , "question_19" => "1" , "question_20" => "1", "contribution_type" => "None" , "proposed_action" => "accept" );
    $vote_base_url="/event/".$cws_config['global']['indico_event_id']."/abstracts/".$abstract_id."/reviews/".$review_id."/edit";
    //$vote_base_url="/event/37/abstracts/109/reviews/16835/edit";
} else {
    //$post_data=array(    "track" => $track_id , "track-".$track_id."-question_$first_question_id" => $question1, "track-".$track_id."-question_$second_question_id" =>  $question2 , "track-".$track_id."-proposed_action" => "accept" );
    //$post_data=array(  "track-".$track_id."-csrf_token" => $csrf_token,   "track" => $track_id , "track-".$track_id."-question_$first_question_id" => $question1, "track-".$track_id."-question_$second_question_id" =>  $question2 , "track-".$track_id."-proposed_action" => "accept" , "track-".$track_id."-proposed_contribution_type" => "23" , "track-".$track_id."-comment" => "None..." );
    //https://indico.jacow.org/event/37/abstracts/125/review/track/85
    $vote_base_url="/event/".$cws_config['global']['indico_event_id']."/abstracts/".$abstract_id."/review/track/".$track_id;
}
if ($code_testing==1) {
    echo "vote_base_url $vote_base_url \n";
    print_r($post_data);
}
$req =$Indico->request( $vote_base_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true, 'use_session_token' => true));
if ($code_testing==1) {
    echo "Post data:\n";
    var_dump($post_data);
    //var_dump($req);
    echo "Result:\n";
}
var_dump($req["success"]);
/*
//Check that the vote was recorded properly
$abtracts_base_url="/event/".$cws_config['global']['indico_event_id']."/manage/abstracts/abstracts.json";
$post_data= array( 'abstract_id' =>  $abstract_id ); 
$req =$Indico->request( $abtracts_base_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true));

var_dump($req);
*/
/*
$track_id=$req["abstracts"][0]["submitted_for_tracks"][0]["id"];
echo "sub track". $track_id."\n";

//var_dump($req["abstracts"][count($req["abstracts"])-1]["submitted_for_tracks"]);
var_dump($req["abstracts"][count($req["abstracts"])-1]);

foreach ( $req["abstracts"][count($req["abstracts"])-1]["reviews"] as $review){    
    echo "review id ". $review["id"]."\n";
    echo "review id ". $review["track"]["id"]."\n";
    echo "review id ". $review["user"]["full_name"]."\n";
    var_dump($review);
}
*/

//post_data="track-"+track_id+"-csrf_token="+token+"&track-"+track_id+"-question_67=1&track-"+track_id+"-question_68=0&track-"+track_id+"-contribution_type=__None&track-"+track_id+"-proposed_action=accept&track-"+track_id+"-comment=";

?>