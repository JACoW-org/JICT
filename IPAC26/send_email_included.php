<?php

/* Created by Nicolas.Delerue@ijclab.in2p3.fr
2025.12.12 1st version

This page is meant to be included into another one and is used to send an email and record a comment in the timeline.

*/
$code_testing=0;

$Indico->api->cfg['header_content_type'] = 'application/json';

$post_data=array();


if (count($_POST)==0){
    die("No POST arguments passed, exiting\n");
    if ($code_testing==1) {
         echo "No POST arguments passed.\n";   
    }
     // abstract_id=113&vote_value=1&review_id=0&track_id=47
    if (count($_GET)>0){
        if ($code_testing==1) {
            echo "Using GET data\n";   
        }
        $abstract_id= $_GET['abstract_id'];
        $subject= $_GET['subject'];
        $body= $_GET['body'];
        $role= $_GET['role'];
    } 
} else {
    $abstract_id= $_POST['abstract_id'];
    $subject= $_POST['subject'];
    $body= $_POST['body'];
    $role= $_POST['role'];
}

if ($_POST["submit"]=="Notify"){
    $post_data["abstract_id"]=array( $abstract_id );
    $post_data["body"]=$body;
    $post_data["subject"]=$subject;
    $post_data["bcc_addresses"]=array();
    //$post_data["copy_for_sender"]=false;
    $post_data["copy_for_sender"]=true;
    $post_data["recipient_roles"]=array( $role );
    $post_data["sender_address"]=$_SESSION['indico_oauth']["user"]["email"];

    if ($code_testing==1) {
        print_r($post_data);
    }
    $target_url="/event/{id}/manage/abstracts/api/email-roles/send";
    $req =$Indico->request( $target_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>false, 'use_session_token' => true));
    //$req =$Indico->request( $target_url , 'POST', json_encode($post_data),  array(  'return_data' =>true, 'quiet' =>true, 'use_session_token' => false));
    if ($code_testing==1) {
        echo "\nPost data:\n";
        var_dump($post_data);
        echo "\nResult:\n";
        var_dump($req);
    }
    if (array_key_exists("success",$req)){
        //nothing
    } else {
        print("No success");
        var_dump($req);
    }
} //submit action

//Recording comment on the abstract

$abstract_id= $_POST['abstract_id'];
if ($_POST["submit"]=="Notify"){
    $comment= "QA Email sent. Reason: ".$_POST['reasons'];
} else if ($_POST["submit"]=="Ignore"){
    $comment= "QA issue ignored. Reason: ".$_POST['reasons'];
}
$req =$Indico->request( "/event/{id}/abstracts/".$abstract_id."/comment", 'POST', array( 'text' => $comment , 'visibility' => "reviewers" ) , array( 'return_data' =>true, 'quiet' =>true, 'use_session_token' => true ) );
if (array_key_exists("success",$req)){
    //nothing
} else {
    print("No success");
    var_dump($req);
}

/*
$post_data=array();
$post_data["abstract_id"]=array(203);
if ($code_testing==1) {
    print_r($post_data);
}
$target_url="/event/37/manage/abstracts/api/email-roles/metadata";
$req =$Indico->request( $target_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true, 'use_session_token' => false, 'disable_cache' => true));
//$req =$Indico->request( $target_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true, 'use_session_token' => true));
//$req =$Indico->request( $target_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>true, 'use_session_token' => true));
if ($code_testing==1) {
    echo "\nPost data:\n";
    //var_dump(json_encode($post_data));
    var_dump($post_data);
    echo "\nResult:\n";
    var_dump($req);
}
sleep(2);
print("<BR/> --- <BR/>\n");


//$post_data=array();
//$post_data["abstract_id"]=array(203);
//$post_data["bcc_addresses"]=array();
$post_data["body"]=$req["body"];
//$post_data["copy_for_sender"]=false;
//$post_data["recipient_roles"]=array("author");
//$post_data["sender_address"]="delerue@lal.in2p3.fr";
$post_data["subject"]="test from indico";

if ($code_testing==1) {
    print_r($post_data);
}
$target_url="/event/37/manage/abstracts/api/email-roles/preview";
$Indico->api->cfg['header_content_type'] = 'application/json';
$req2 =$Indico->request( $target_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>false, 'use_session_token' => false , 'disable_cache' => true, 'header_content_type' => 'application/json'));
//$req =$Indico->request( $target_url , 'POST', json_encode($post_data),  array(  'return_data' =>true, 'quiet' =>true, 'use_session_token' => true));
if ($code_testing==1) {
    echo "\nPost data:\n";
    var_dump($post_data);
    echo "\nResult:\n";
    var_dump($req2);
} else {
var_dump($req2);
}

sleep(2);
print("<BR/> --- <BR/>\n");

//$post_data=array();
//$post_data["abstract_id"]=array(203);
$post_data["bcc_addresses"]=array();
//$post_data["body"]="<p>Dear {first_name},<br><br>This is a test from php<br><br>Best regards<br>Nicolas Delerue</p>";
$post_data["copy_for_sender"]=false;
$post_data["recipient_roles"]=array("author");
$post_data["sender_address"]="delerue@lal.in2p3.fr";
//$post_data["subject"]="test from indico";

if ($code_testing==1) {
    print_r($post_data);
}
$target_url="/event/37/manage/abstracts/api/email-roles/send";
$req3 =$Indico->request( $target_url , 'POST', $post_data,  array(  'return_data' =>true, 'quiet' =>false, 'use_session_token' => false));
//$req =$Indico->request( $target_url , 'POST', json_encode($post_data),  array(  'return_data' =>true, 'quiet' =>true, 'use_session_token' => false));
if ($code_testing==1) {
    echo "\nPost data:\n";
    var_dump($post_data);
    echo "\nResult:\n";
    var_dump($req3);
} else {
var_dump($req3);
}

*/

/*
https://indico.jacow.org/event/37/manage/abstracts/api/email-roles/send
[HTTP/2 200  107ms]

	
abstract_id	(1)[…]
0	203
bcc_addresses	[]
body	"<p>Dear {first_name},<br><br><br><br>Best regards<br>Nicolas Delerue</p>"
copy_for_sender	false
recipient_roles	(1)[…]
0	"author"
sender_address	"delerue@lal.in2p3.fr"
subject	"Test"
*/
?>