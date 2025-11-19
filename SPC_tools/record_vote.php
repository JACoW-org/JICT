<?php

/* Created by Nicolas.Delerue@ijclab.in2p3.fr
2025.11.17 1st version

This page records the vote on a given abstract passed by ID in a POST request.

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

$first_question_id=$cws_config['SPC_tools']['first_question_id'];
$second_question_id=$cws_config['SPC_tools']['second_question_id'];


if (count($_POST)==0){
     echo "No arguments passed, using defaults\n";   
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
     die("No arguments passed, exiting\n");
} else {
    $abstract_id= $_POST['abstract_id'];
    $vote_value= $_POST['vote_value'];
    $review_id= $_POST['review_id'];
    $track_id= $_POST['track_id'];
}
/*
echo "abstract_id $abstract_id \n";
echo "vote_value $vote_value \n";
echo "review_id $review_id \n";
echo "track_id $track_id \n";
*/

/*
$abtracts_base_url="/event/".$cws_config['global']['indico_event_id']."/manage/abstracts/abstracts.json";
echo "abtracts_base_url $abtracts_base_url \n";


$post_data= array( 'abstract_id' =>  $abstract_id ); 
$req =$Indico->request( $abtracts_base_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true));
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
//echo "Full req\n";
//var_dump($req["abstracts"]);
//var_dump($req);

/*
//post comment
$post_data=array( 'text' => "test1711_1544" , 'visibility' => "judges" , "track" => 85 , "question_1" => "1" , "question_2" => "0" );

$vote_base_url="/event/".$cws_config['global']['indico_event_id']."/abstracts/".$abstract_id."/comment";
echo "vote_base_url $vote_base_url \n";
$req =$Indico->request( $vote_base_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true));
echo "Full req 2\n";
var_dump($post_data);
var_dump($req["success"]);
*/
/*
    //post_data="track-"+track_id+"-csrf_token=025b2a88-7905-44c3-9ebc-be6926ef4ecc&track-"+track_id+"-question_67=1&track-"+track_id+"-question_68=0&track-"+track_id+"-contribution_type=__None&track-"+track_id+"-proposed_action=accept&track-"+track_id+"-comment=";
    if (value==1){
        post_data="track-"+track_id+"-csrf_token="+token+"&track-"+track_id+"-question_67=1&track-"+track_id+"-question_68=0&track-"+track_id+"-contribution_type=__None&track-"+track_id+"-proposed_action=accept&track-"+track_id+"-comment=";
*/

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

if ($review_id>0){
    $post_data=array(  "track" => $track_id , "track-".$track_id."-question_$first_question_id" => $question1, "track-".$track_id."-question_$second_question_id" =>  $question2 , "track-".$track_id."-proposed_action" => "accept");
    //$post_data=array( 'track-'.$track_id.'-comment' => 'from PHP track 47' , "track" => $track_id , "track-".$track_id."-question_20" => 0 , "track-".$track_id."-question_19" => 0, "track-".$track_id."-proposed_action" => "accept");
    //$post_data=array( 'comment' => "test1711_1617r" , 'visibility' => "conveners", "track" => 47 , "question_1" => "1" , "question_2" => "1" , "question_19" => "1" , "question_20" => "1", "contribution_type" => "None" , "proposed_action" => "accept" );
    $vote_base_url="/event/".$cws_config['global']['indico_event_id']."/abstracts/".$abstract_id."/reviews/".$review_id."/edit";
    //$vote_base_url="/event/37/abstracts/109/reviews/16835/edit";
} else {
    $post_data=array(  "track" => $track_id , "track-".$track_id."-question_19" => $question1, "track-".$track_id."-question_20" =>  $question2 , "track-".$track_id."-proposed_action" => "accept");
    //https://indico.jacow.org/event/37/abstracts/125/review/track/85
    $vote_base_url="/event/".$cws_config['global']['indico_event_id']."/abstracts/".$abstract_id."/review/track/".$track_id;
}
echo "vote_base_url $vote_base_url \n";
$req =$Indico->request( $vote_base_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true));
echo "Full req 3\n";
var_dump($post_data);
var_dump($req);


//post_data="track-"+track_id+"-csrf_token="+token+"&track-"+track_id+"-question_67=1&track-"+track_id+"-question_68=0&track-"+track_id+"-contribution_type=__None&track-"+track_id+"-proposed_action=accept&track-"+track_id+"-comment=";

?>