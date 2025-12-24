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

$T =new TMPL( $cfg['template_my_votes'] );
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


//Create the vote table
$vote_table_content="";
$vote_table_content.="<P><center>\n";
$vote_table_content.="<h3>Your votes summary</h3>\n";
$vote_table_content.="<div class=\"table-wrap\"><table id=\"votes\" class=\"vote_table\">\n";
$vote_table_content.="  <caption> Your votes\n";
$vote_table_content.="  </caption>\n";
$vote_table_content.="  <thead>\n";
$vote_table_content.="  <tr>\n";
$vote_table_content.="  <th></th>\n";
for ($imc=1; $imc<=8; $imc++){
    $vote_table_content.="  <th>MC".$imc."</th>\n";
}
$vote_table_content.="  </tr>\n";
$vote_table_content.="</thead>  <tbody>\n";
for($ivote=1; $ivote<=3; $ivote++){     
    $vote_table_content.="  <tr>\n";
    if ($ivote==1){
        $choice="1";
        $vote_table_content.="  <td>First priority</td>\n";
    } else if ($ivote==2){
        $choice="2";
        $vote_table_content.="  <td>Second priority</td>\n";
    } else {
        $vote_table_content.="  <td>Expected votes </td>\n";
    }
    for ($imc=1; $imc<=8; $imc++){
        if (($ivote==1)||($ivote==2)){
            $vote_table_content.="  <td ";
            if ($your_votes["MC".$imc][$choice]){
                if (count($your_votes["MC".$imc][$choice])==$cws_config['SPC_tools']['votes_to_cast_by_MC'][$imc-1]){
                    $vote_table_content.="style=\"background-color: #82E0AA ;\""; //green
                } else if (count($your_votes["MC".$imc][$choice])<$cws_config['SPC_tools']['votes_to_cast_by_MC'][$imc-1]){
                    $vote_table_content.="style=\"background-color:  #F7DC6F ;\""; //yellow
                } else {
                    $vote_table_content.="style=\"background-color:  #F1948A ;\""; //red
                }
                $vote_table_content.=" >";
                $vote_table_content.=count($your_votes["MC".$imc][$choice]);
            } else {
                $vote_table_content.="style=\"background-color: #F7DC6F ;\""; //yellow
                $vote_table_content.=" >";
                $vote_table_content.=" - \n";  
            }
        } else {
            $vote_table_content.="  <td>";
            $vote_table_content.=" 2 * ".$cws_config['SPC_tools']['votes_to_cast_by_MC'][$imc-1]."\n";
        }
        $vote_table_content.="</td>\n";  
    }
    $vote_table_content.="  </tr>\n";
}
$vote_table_content.="  </tbody>\n";
$vote_table_content.="</table></div>\n";
$vote_table_content.="<A HREF='vote.php?text=0'>Click here to go back to the list of abstracts (without full text).</A><BR/>\n";
$vote_table_content.="<A HREF='vote.php?text=1'>Click here to go back to the list of abstracts with full text.</A><BR/>\n";
$vote_table_content.="</center></P>\n";

$content =$vote_table_content.$content;



//*** Your votes ***/

//var_dump($your_votes);

//$content .="<P><center>\n";
$content .="<h3>Your votes</h3>\n";
//$content .="</center>\n";



for ($imc=1; $imc<=8; $imc++){
    $content .=" <P>\n";
    $content .="<h4>MC".$imc."</h4>\n";
    if (!$your_votes["MC".$imc]){
        $content .="No vote in this MC<BR/>\n";
    } else {
        foreach(array("1", "2") as $choice){
            $content .="<h5>";               
            if ($choice=="1"){
                 $content .="First choice";               
            } else if ($choice=="2"){
                 $content .="Second choice";               
            }
            $content .="</h5>";

            if (count($your_votes["MC".$imc][$choice])>0){
                $content .=" <ul>\n";
                foreach ($your_votes["MC".$imc][$choice] as $abs){
                    $content .=" <li>Abstract <A HREF='https://indico.jacow.org/event/".$cfg['indico_event_id'].'/abstracts/'.$abs."'>". $abs." (".$all_abstracts[$abs]['title'].")</A>\n";
                    $content .="</li>\n";
                }
                $content .=" </ul>\n";
            } else {
                $content .="No vote in this MC with this priority.<BR/>\n";
            }
        } //foreach choice
        $content .=" <BR/>\n";
    } //if votes
    $content .=" </P>\n";
} //foreach MC



$content .=" <BR/>\n";
$content .=" <BR/>\n";
$content .=" <BR/>\n";
$content .=" <BR/>\n";
$content .=" <BR/>\n";

$T->set( 'content', $content );
for ($imc=1; $imc<=8; $imc++){
    $mc_sum=count($your_votes["MC".$imc]["1"])+count($your_votes["MC".$imc]["2"]);
    $T->set( 'MC'.$imc.'_n', "".count($your_votes["MC".$imc]["1"])."+ ".count($your_votes["MC".$imc]["2"])." = ".$mc_sum );
}
$T->set( 'event_id', $cws_config['global']['indico_event_id'] );
$T->set( 'user_name', $_SESSION['indico_oauth']["user"]["full_name"]);
$T->set( 'user_first_name', $_SESSION['indico_oauth']["user"]["first_name"]);
$T->set( 'user_last_name',$_SESSION['indico_oauth']["user"]["last_name"]);
echo $T->get();

//var_dump($your_votes);

?>