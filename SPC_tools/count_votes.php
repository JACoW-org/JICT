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

require( 'autoconfig.php' );

$first_question_id=$cws_config['SPC_tools']['first_question_id'];
$second_question_id=$cws_config['SPC_tools']['second_question_id'];

$T =new TMPL( $cfg['template_count'] );
$T->set([
    'style' =>'
        main { font-size: 14px; margin-bottom: 2em } 
        td.b_x { background: #555; color: white } 
        td.b_y2g { background: #ADFF2F; color: black }
        tr:hover td { background: #b0f4ff; color: black }
        tr.warn td { background: #ffbab0; color: black }

        ',
    'title' =>$cfg['name'],
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
    'path' =>'../',
    'head' =>"<link rel='stylesheet' type='text/css' href='../dist/datatables/datatables.min.css' />
    <link rel='stylesheet' type='text/css' href='../page_edots/colors.css' />
    <link rel='stylesheet' type='text/css' href='../style.css' />",
    'scripts' =>"<script src='../dist/datatables/datatables.min.js'></script>",
    'js' =>false
    ]);

$content =false;
$content ="<BR/>";
$content .="<BR/>";
$content .="<BR/>";

require_once('counter.php');

//var_dump($change_tracks);

//*** Abstracts types ***/
foreach (array_keys($abstracts_states_count) as $state){
    $content .="Abstracts with state ".$state.": ".$abstracts_states_count[$state]."<BR/>\n"; 
}

//*** Votes summary ***/
$content .="<P><center>\n";
$content .="<h3>Votes summary</h3>\n";
$content .="<div class=\"table-wrap\"><table id=\"votes\" class=\"vote_table\">\n";
$content .="  <caption> Votes counted\n";
$content .="  </caption>\n";


$content .="<TABLE class=\"vote_table\">\n"; 
$content .="<thead>\n";
$content .="<TR>\n"; 
$content .="<Th  > Voter </Th>"; 
for ($imc=1;$imc<=8;$imc++){
    $content .="<Th colspan=\"2\"> MC".$imc." </Th>"; 
}
$content .="<Th> Total </Th>"; 
$content .="</TR>\n"; 
$content .="</thead>\n";
$content .="<tbody>\n";

ksort($votes_count);
$total_all=0;
foreach (array_keys($votes_count) as $voter){
    $total=0;
    $content .="<TR>\n"; 
    $content .="<TD>".$voter."</TD>"; 
    for ($imc=1;$imc<=8;$imc++){
        if (array_key_exists("MC".$imc,$votes_count[$voter])){
            $content .="<TD ";
            if ($votes_count[$voter]["MC".$imc]["1"]==$cws_config['SPC_tools']['votes_to_cast_by_MC'][$imc-1]){
                 $content .="style=\"background-color: #82E0AA ;\""; //green
            } else if ($votes_count[$voter]["MC".$imc]["1"]<$cws_config['SPC_tools']['votes_to_cast_by_MC'][$imc-1]){
                $content .="style=\"background-color: #F7DC6F ;\""; //yellow
            } else {
                $content .="style=\"background-color:  #F1948A ;\""; //red
            }
            $content .=" >".$votes_count[$voter]["MC".$imc]["1"]."</TD>\n";

            $content .="<TD ";
            if ($votes_count[$voter]["MC".$imc]["2"]==$cws_config['SPC_tools']['votes_to_cast_by_MC'][$imc-1]){
                 $content .="style=\"background-color: #82E0AA ;\""; //green
            } else if ($votes_count[$voter]["MC".$imc]["2"]<$cws_config['SPC_tools']['votes_to_cast_by_MC'][$imc-1]){
                $content .="style=\"background-color: #F7DC6F ;\""; //yellow
            } else {
                $content .="style=\"background-color:  #F1948A ;\""; //red
            }
            $content .=" >".$votes_count[$voter]["MC".$imc]["2"]."</TD>\n"; 
            $total+=$votes_count[$voter]["MC".$imc]["1"];
            $total+=$votes_count[$voter]["MC".$imc]["2"];
        } else {
            $content .="<TD> - </TD><TD> - </TD>"; 
        }
    }
    $content .="<TD> ".$total." </TD>"; 
    $total_all+=$total;
    $content .="</TR>\n"; 
}
$content .="</tbody>\n";

$content .="</TABLE>\n"; 
$content .="</center>\n";

$content .="</P>\n";


//*** Track changes ***/

$content .="<P><center>\n";
$content .="<h3>Track changes</h3>\n";
$content .="</center>\n";

for ($imc=1; $imc<=8; $imc++){
    $content .="<h4>To MC".$imc."</h4>\n";
    if (!$change_tracks["MC".$imc]){
        $content .="No request in this MC<BR/>\n";
    } else {
        //print_r(array_keys($change_tracks["MC".$imc]));
        $content .="Changes requested on ". count(array_keys($change_tracks["MC".$imc]))." abstract(s).<BR/>\n";
        $content .=" <ul>\n";
        if (count($change_tracks["MC".$imc])>0){
            //print_r(array_keys($change_tracks["MC".$imc]));
            foreach (array_keys($change_tracks["MC".$imc]) as $abs){
                $content .=" <li>Abstract <A HREF='https://indico.jacow.org/event/".$cfg['indico_event_id'].'/abstracts/'.$abs."'> #". $all_abstracts[$abs]["friendly_id"]." (".$all_abstracts[$abs]['title'].")</A><ul>\n";
                foreach (array_keys($change_tracks["MC".$imc][$abs]) as $target){
                    $content .="<li>Move abstract  <A HREF='https://indico.jacow.org/event/".$cfg['indico_event_id'].'/abstracts/'.$abs."'> #". $all_abstracts[$abs]["friendly_id"]." (".$all_abstracts[$abs]['title'].")</A> from track ".$all_abstracts[$abs]["submitted_for_tracks"][0]["code"]." to track ".$target.": ".$change_tracks["MC".$imc][$abs][$target]." vote";
                    if ($change_tracks["MC".$imc][$abs][$target]>1){
                    $content .="s";
                    }
                    $content .="</li>\n";
                }
            $content .="</ul></li>\n";
            }
        $content .=" </ul>\n";
        }
    }
}

//*** Ranking ***/
if (strtotime("now")<strtotime("2026-01-15 12:00")){
    //print("Not yet");
} else{
    $content .="<P><center>\n";
    $content .="<h3>Ranking</h3>\n";
    $content .="</center>\n";

    $score=[];
    foreach($all_abstracts as $abs){
        $score1st[$abs["id"]]=$abs["1"];
        $score[$abs["id"]]=2*$abs["1"]+$abs["2"];
        $all_abstracts[$abs["id"]]["vote_score"]=2*$abs["1"]+$abs["2"];
    }

    array_multisort($score1st,SORT_DESC,$all_abstracts,$score);
    array_multisort($score,SORT_DESC,$all_abstracts);
    //print("\n\n\nsorted\n");
    //var_dump($score);
    //var_dump($all_abstracts);
    /*
    print_r(reset($all_abstracts));
    print_r(next($all_abstracts));
    print_r(next($all_abstracts)["id"]);
    print_r(next($all_abstracts)["id"]);
    */
    for ($imc=1; $imc<=8; $imc++){
        $ranking["MC". $imc]="<P><h4>MC".$imc."</h4><ul>\n";
    }
    foreach($all_abstracts as $abs){
        if ($abs["vote_score"]>0){
            $ranking[$abs["MC"]] .=" <li> Score ". $abs["vote_score"]. " ( 2*".$abs["1"]. " + ".$abs["2"].") <A HREF='https://indico.jacow.org/event/".$cfg['indico_event_id'].'/abstracts/'.$abs["id"]."'> Abstract #". $abs["friendly_id"]." (".$abs['title'].")</A></li>\n";
        }
    }
    for ($imc=1; $imc<=8; $imc++){
        $content .=$ranking["MC". $imc]."</ul></P>";
    } 
} // if time is OK

$content .=" <BR/>\n";
$content .=" <BR/>\n";
$content .=" <BR/>\n";
$content .=" <BR/>\n";
$content .=" <BR/>\n";

$T->set( 'content', $content );
/*
for ($imc=1; $imc<=8; $imc++){
    $mc_sum=$votes_count["MC".$imc][1]+$votes_count["MC".$imc][2];
    $T->set( 'MC'.$imc.'_n', "".$votes_count["MC".$imc][1]."+".$votes_count["MC".$imc][2]." = ".$mc_sum );
}
*/
//$T->set( 'column_width', $column_width);
$T->set( 'submitted', $abstracts_states_count["submitted"]);
$T->set( 'total_votes', $total_all);
$T->set( 'total_votes_expected', 80*count(array_keys($votes_count)));
$T->set( 'event_id', $cws_config['global']['indico_event_id'] );
$T->set( 'user_name', $_SESSION['indico_oauth']["user"]["full_name"]);
$T->set( 'user_first_name', $_SESSION['indico_oauth']["user"]["first_name"]);
$T->set( 'user_last_name',$_SESSION['indico_oauth']["user"]["last_name"]);
echo $T->get();

//var_dump($your_votes);

?>