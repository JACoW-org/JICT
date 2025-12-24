<?php

/* Created by Nicolas.Delerue@ijclab.in2p3.fr
2025.12.23 1st version

This code is called by several code to parse and count the abstracts

*/

/*** Counting ***/
$_rqst_cfg=[];
$_rqst_cfg['disable_cache'] =true;
$data_key= $Indico->request( '/event/{id}/manage/abstracts/abstracts.json', 'GET', false, $_rqst_cfg);
$abstracts_states_count=[];
$votes_count=[];
$all_abstracts=[];
$change_tracks=[];

$your_votes=[];

for ($imc=1;$imc<=8;$imc++){
    $your_votes["MC".$imc]["1"]=[];
    $your_votes["MC".$imc]["2"]=[];
    $your_votes["MC".$imc]["3"]=[];
    $your_votes["MC".$imc]["change_track"]=[];
    $change_tracks["MC".$imc]=[];
}


$roles =$Indico->request( '/event/{id}/manage/roles/api/roles/', 'GET', false, [ 'return_data' =>true ]);
//var_dump($roles);
$voters=[];
foreach ($roles as $role) {
    if (in_array($role["code"],array("SPC","SPM","SPO"))){
        foreach($role["members"] as $member){
            /*
            $voter_found=false;
            foreach($voters as $voter){
                if ($voter["email"]==$member["email"]){
                    $voter_found=true;
                }
            }            
            if (!($voter_found)){
                $voters[]=$member;
            }
            */
            $votes_count[$member["full_name"]]=[];
        }
    }
}



foreach ($Indico->data[$data_key]['abstracts'] as $abstract) {
    if (!(array_key_exists($abstract["state"],$abstracts_states_count))){
        $abstracts_states_count[$abstract["state"]]=0;
    }
    $all_abstracts[$abstract["id"]]=$abstract;
    $all_abstracts[$abstract["id"]]["1"]=0;
    $all_abstracts[$abstract["id"]]["2"]=0;
    $all_abstracts[$abstract["id"]]["3"]=0;
    $all_abstracts[$abstract["id"]]["MC"]=substr($abstract["submitted_for_tracks"][0]["code"],0,3);

    $abstracts_states_count[$abstract["state"]]+=1;
    $abstract["MC"]=substr($abstract["submitted_for_tracks"][0]["code"],0,3);
    foreach($abstract["reviews"] as $review){
        if (!(array_key_exists($review["user"]["full_name"],$votes_count))){
            print("Warning: vote by an unexpected user: ".$review["user"]["full_name"]."\n");
            //$votes_count[$review["user"]["full_name"]]=[];
        } else {
            if (!(array_key_exists($abstract["MC"],$votes_count[$review["user"]["full_name"]]))){
                $votes_count[$review["user"]["full_name"]][$abstract["MC"]]=[];
                $votes_count[$review["user"]["full_name"]][$abstract["MC"]]["1"]=0;
                $votes_count[$review["user"]["full_name"]][$abstract["MC"]]["2"]=0;
                $votes_count[$review["user"]["full_name"]][$abstract["MC"]]["3"]=0;
            }
            $current_vote="3";
            if (($review["proposed_action"]=="accept")||($review["proposed_action"]=="change_tracks")){            
                foreach($review["ratings"] as $rating){
                    if ($rating["question"]==$first_question_id){                    
                        if ($rating["value"]==true){
                            $current_vote="1";
                        }
                    } else if ($rating["question"]==$second_question_id){
                        if ($current_vote!="1"){
                            if ($rating["value"]==true){
                                $current_vote="2";
                            }
                        }  
                    }     
                } //for each rating
                if (!($abstract["state"]=="submitted")){
                    if (!($current_vote=="3")){
                            $content.=$review["user"]["full_name"]." has voted on an abstract (".$abstract["id"].") that is ".$abstract["state"].".<BR/>";
                    } 
                }
                $votes_count[$review["user"]["full_name"]][$abstract["MC"]][$current_vote]+=1;
                $all_abstracts[$abstract["id"]][$current_vote]+=1;

                if ($review["user"]["full_name"]==$_SESSION['indico_oauth']["user"]["full_name"]){
                    $your_votes[$abstract["MC"]][$current_vote][]=$abstract["id"];
                    if ($review["proposed_action"]=="change_tracks"){
                        $your_votes[$abstract["MC"]]["change_track"][]=$abstract["id"];
                    }
                }

                if (($review["proposed_action"]=="change_tracks")&&($abstract["state"]=="submitted")){
                    //print_r($review);
                    $new_track_id=$review["proposed_tracks"][0]["id"];
                    $new_track_code=$review["proposed_tracks"][0]["code"];
                    $target_MC=substr($review["proposed_tracks"][0]["code"],0,3);
                    $target_track=substr($review["proposed_tracks"][0]["code"],0,7);
                    if (!(array_key_exists($abstract["id"],$change_tracks[$target_MC]))){
                        $change_tracks[$target_MC][$abstract["id"]]=[];
                    }
                    if (!(array_key_exists($target_track,$change_tracks[$target_MC][$abstract["id"]]))){
                        $change_tracks[$target_MC][$abstract["id"]][$target_track]=0;
                    }
                    $change_tracks[$target_MC][$abstract["id"]][$target_track]+=1;
                    #array_push($change_tracks[$target_MC][$abstract["id"]][$target_track],$review["user"]["full_name"]." proposes to move abstract ".$abstract["id"]." to track ".$review["proposed_tracks"][0]["code"]."<BR/>");
                }
            } //accept or change track
        } // if voter exists
    } //for each review
} //for each abstract
?>
