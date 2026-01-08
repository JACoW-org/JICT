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

//print('autoconfig');

$tracksfile=$cws_config['global']['data_path']."/conference_tracks.json";
$questionsfile=$cws_config['global']['data_path']."/questions_numbers.json";
/*
if ($cws_config['global']['indico_event_id']==37) {
    $cws_config['SPC_tools']['first_question_id'] =19; //to find this value check an abstract on which you voted
    $cws_config['SPC_tools']['second_question_id'] = 20;
} else if ($cws_config['global']['indico_event_id']==95) {
    $cws_config['SPC_tools']['first_question_id'] =67; //to find this value check an abstract on which you voted
    $cws_config['SPC_tools']['second_question_id'] = 68;
}
*/
//load questions file
$questions=file_read_json( $questionsfile, true );
if ((!($questions))||(str_contains($_SERVER["QUERY_STRING"],"reload_config"))){

    //Trying to get question ID:

    $data_key= $Indico->request( '/event/{id}/manage/abstracts/abstracts.json', 'GET', false, false);
    $qid=false;
    while((!$qid)&&($abstract=next($Indico->data[$data_key]['abstracts']))){
        $base_url='/event/{id}/abstracts/'.$abstract['id'].'/review/track/'.$abstract["submitted_for_tracks"][0]['id'];
        print($base_url);
        $abstract_key= $Indico->request( $base_url, 'GET', false, $_rqst_cfg);
        //var_dump($abstract_key);
        //var_dump($Indico->data[$abstract_key]);
        print("<BR/>\n");
        if ($Indico->data[$abstract_key]) {
            $matches = null;
            //$returnValue = preg_match_all("#<div id=\\\"track-".$abstract["submitted_for_tracks"][0]['id']."-question_(.*)\\\"#", $Indico->data[$abstract_key] , $matches);
            $returnValue = preg_match_all("#track-".$abstract["submitted_for_tracks"][0]['id']."-question_([0-9]+)\"#", $Indico->data[$abstract_key]["html"] , $matches);
            var_dump(array_unique($matches[1])); 
            $cws_config['SPC_tools']['first_question_id'] =  current(array_unique($matches[1]));      
            $cws_config['SPC_tools']['second_question_id'] = next(array_unique($matches[1]));
            var_dump(array_unique($matches[1])); 
            print('first_question_id= '.$cws_config['SPC_tools']['first_question_id']);  
            print('second_question_id= '.$cws_config['SPC_tools']['second_question_id']);  
            $questions=[];
            $questions['first_question_id']=$cws_config['SPC_tools']['first_question_id'];
            $questions['second_question_id']=$cws_config['SPC_tools']['second_question_id'];
            $fwquestions=file_write_json($questionsfile,$questions);
            $qid=true;
        } // if match
    } // while each abstract
} else {
    $cws_config['SPC_tools']['first_question_id']=$questions['first_question_id'];
    $cws_config['SPC_tools']['second_question_id']=$questions['second_question_id'];
}



//parse the first abstract of asbtracts.json view-source:https://indico.jacow.org/event/37/abstracts/115/reviews/16839/edit and look for "-question_"
// or view-source:https://indico.jacow.org/event/37/abstracts/183/review/track/86

if (!(array_key_exists('tracks', $cws_config['SPC_tools']))){
    //load tracks file
    $tracks=file_read_json( $tracksfile, true );
    if ((!($tracks))||(str_contains($_SERVER["QUERY_STRING"],"reload_config"))){
        if (!($tracks)){
            //if unable to read the tracks file
            print("Unable to read the tracks, fetching them again!");
        } else {
            print("Reloading the tracks config!");
        }
        $tracks=[];
        //parse /event/95/manage/tracks/ to find the track labels (requires sufficient rights) and saves them in a json file.

        $req =$Indico->request( sprintf("/event/%s/manage/tracks/", $cws_config['global']['indico_event_id'] ), 'GET', false, array(  'return_data' =>true, 'quiet' =>true ) );
        $matches = null;
        $returnValue = preg_match_all("#<li class=\"track-row i-box\" data-id=\"(.*)\">.*\n.*\n.*\n.*<span class=\"i-box-title\">(.*)</span>#", $req , $matches);
        $labelMatches = null;
        $labelReturnValue = preg_match_all("#<span class=\"i-label small\">.*\n(.*)\n.*</span>#", $req , $labelMatches);
        $tracks=[];
        if ($returnValue!=$labelReturnValue){
            print("Error returnValue!=labelReturnValue");
            die("Unable to identify tracks");
        } else {
            for($iloop=0;$iloop<$returnValue;$iloop++){
                if (str_contains($matches[2][$iloop],trim($labelMatches[1][$iloop]))){
                    $tracks[]=array( "id"=> $matches[1][$iloop], "name" => $matches[2][$iloop], "code" => trim($labelMatches[1][$iloop]));
                } else {
                    print("Error code (".trim($labelMatches[1][$iloop]).")not in name (".$matches[2][$iloop].")");
                    die("Unable to identify tracks");
                }
            }
        }
        print("Tracks:<BR/>\n");
        print_r($tracks);
        print("<BR/>\n");
        $fwtracks=file_write_json($tracksfile,$tracks);
    } //re-read tracks config
    //print_r($tracks);
    $cws_config['SPC_tools']['tracks']=$tracks;
} // if tracks not defined
?>
