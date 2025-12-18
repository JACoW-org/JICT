<?php

/* by Nicolas Delerue

2025.09.03 Creation
2025.11.14 Update. Note: this page uses HTML parsing instead of json data. This should be fixed/rewritten at some point.

*/
if (str_contains($_SERVER["QUERY_STRING"],"debug")){
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} //if debug on


require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'registrations' );
$cfg['verbose'] =1;


$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

$Indico->load();
//$Indico->import_registrants(); // does not load all the info

$incompatibilities = $cws_config['registrations']["incompatibilities"];
$dates_check =$cws_config['registrations']["dates_check"];


//Known incompatibilities file
$known_incompatibilities=file_read_json( $cws_config['global']['data_path']."/known_incompatibilities.json", true );
if (!($known_incompatibilities)){
    $known_incompatibilities=[];
}
if (count($_POST)>0){
    $known_incompatibilities[]=["user-id"=>$_POST["user-id"],"type"=>$_POST["type"],"criteria"=>$_POST["criteria"],"until"=>$_POST["until"]];
}
$fwret=file_write_json( $cws_config['global']['data_path']."/known_incompatibilities.json",$known_incompatibilities);
//echo "file write $fwret \n"; 


//var_dump($known_incompatibilities);

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

//$this->verbose( "Check registrants" );

$content =false;
$content ="<BR/>\n";
//$content ="URL: ".sprintf("/event/%s/manage/registration/%s/registrations/customize", $cws_config['global']['indico_event_id'] , $cws_config['indico_stats_importer']['registrants_form_id'])."<BR/>";


$req =$Indico->request( sprintf("/event/%s/manage/registration/%s/registrations/customize", $cws_config['global']['indico_event_id'] , $cws_config['indico_stats_importer']['registrants_form_id']), 'GET', false, array(  'return_data' =>true, 'quiet' =>true ) );

$matches = null;
$returnValue = preg_match_all("#data-id=\"(.*)\">#", $req["html"] , $matches);


$visible_items='[ ';
foreach ($matches[1] as $vitem) {
    if (preg_match("/[[:digit:]]+/",$vitem)){
        $visible_items=$visible_items.' '.$vitem.','; 
    } else {
        $visible_items=$visible_items.' "'.$vitem.'",'; 
    }
} // for each field
$visible_items=substr($visible_items,0,strlen($visible_items)-1);
$visible_items=$visible_items." ] "; 

$req =$Indico->request(  sprintf("/event/%s/manage/registration/%s/registrations/customize", $cws_config['global']['indico_event_id'] , $cws_config['indico_stats_importer']['registrants_form_id']), 'POST', array(  'visible_items' =>  $visible_items ) , array(  'return_data' =>true, 'quiet' =>true));
//var_dump($req);

$header_matches=null;
$returnValue = preg_match_all('#<th class="i-table" data-sorter="text">(.*)</th>#', $req["html"] , $header_matches);
$cols_title=$header_matches[1];

$content .= "<b>Checking for incompatibilities:</b>";
$content .= " </BR>";

$start_matches = null;
$returnValue = preg_match_all('#<tr id="registration-([[:alnum:]]*)"#', $req["html"] , $start_matches , PREG_OFFSET_CAPTURE );


$end_matches=null;
$returnValue = preg_match_all('#</tr>#', $req["html"] , $end_matches , PREG_OFFSET_CAPTURE );

if (count($start_matches[1])==0){
    $content .="<b>Please reload the page</b>\n"; 
    $content .="<script>window.location.reload();</script>\n";
} //if data loading failed

$incompatibility_error=false;
foreach ($incompatibilities as $incompatibility){
    foreach (array_keys($incompatibility) as $criteria){
        $idx=array_search($criteria, $cols_title);
        if (!($idx)){
            $incompatibility_error=true;
            $content.="Warning: criteria ".$criteria." was not found. <BR/>\n";
            print("Warning: criteria ".$criteria." was not found. idx=".$idx."<BR/>\n");
        }
    }
}
if ($incompatibility_error){
    var_dump($cols_title);
}



$incompatibilities_found=0;
$incompatibilities_ignored=0;
$total_registrations=0;
for ($imatch=0;$imatch<count($start_matches[1]);$imatch++){
    $regid=$start_matches[1][$imatch][0];
    //print("regid: ".$regid."\n");
    $this_entry=substr($req["html"],$start_matches[1][$imatch][1],$end_matches[0][$imatch+1][1]-$start_matches[1][$imatch][1]);
    $entry_matches=null;
    $returnValue = preg_match_all('#<td class=\"i-table\"(.*)>#', $this_entry , $entry_matches );   
    $entry_data=$entry_matches[1];
    array_shift($entry_data);
    array_shift($entry_data);
    array_shift($entry_data);
    
    
    $criteria="State";
    $idx=array_search($criteria, $cols_title);
    
    if (!(str_contains($entry_data[$idx],"Withdrawn"))){
        $total_registrations+=1;
        for($icol=0;$icol<count($entry_data);$icol++){
            $entry_data[$icol]=trim(preg_replace("/ data-text=\"(.*)\"/", '\1',$entry_data[$icol]));
            $entry_data[$icol]=trim(str_replace("></td","",$entry_data[$icol]));
        }
        $entry_name =  "<A HREF='". sprintf("https://indico.jacow.org/event/%s/manage/registration/%s/registrations/%s", $cws_config['global']['indico_event_id'] , $cws_config['indico_stats_importer']['registrants_form_id'],$regid)."/'> $regid </A>". $entry_data[array_search("First Name", $cols_title)]." " .  $entry_data[array_search("Last Name", $cols_title)] ." ("  . $entry_data[array_search("Email Address", $cols_title)].")\n";
        $show_all_answers=False;
        if ($show_all_answers){
            for ($ival=0;$ival<count($entry_data);$ival++){
                echo $cols_title[$ival]." - ".$entry_data[$ival]." <BR/>\n"; 
            }
        }
        /*
        if ($regid=="9481"){
            $criteria="Date of birth";
            var_dump($entry_data);
            $idx=array_search($criteria, $cols_title);
            print("idx ".$idx.": - ".$criteria." - ".$entry_data[$idx]."\n");
            if (!($entry_data[$idx]==$incompatibility[$criteria])){
                print("Not incompatible");
            } else {
                print("Incompatible");
            }
            print(" <BR/>\n");
            for ($ival=0;$ival<count($entry_data);$ival++){
                echo $cols_title[$ival]." - ".$entry_data[$ival]." <BR/>\n"; 
            }
        }
        */             
        //Checking incomptibilities
        foreach ($incompatibilities as $incompatibility){
            $is_compatible=False;
            //var_dump($incompatibility);
            //var_dump(array_keys($incompatibility));
            $choice_text = "";        
            foreach (array_keys($incompatibility) as $criteria){
                //var_dump($incompatibility[$criteria]);
                //var_dump($criteria);
                $choice_text .= "$criteria= ";
                $idx=array_search($criteria, $cols_title);
                if (!($idx)){
                    $content.="Warning: criteria ".$criteria." was not found. <BR/>\n";
                    print("Warning: criteria ".$criteria." was not found. idx=".$idx."<BR/>\n");
                    //var_dump($cols_title);
                } else {
                    //$choice_text .= $idx ."  ". $entry_data[$idx] ." ". $incompatibility[$criteria] . "<BR/>\n";             
                    $choice_text .= $entry_data[$idx] . "<BR/>\n";             
                    if (!($entry_data[$idx]==$incompatibility[$criteria])){
                        $is_compatible=True;
                        //print("Compatible ".$criteria);
                    } else {
                        //print("Incompatible ".$criteria.": ".$incompatibility[$criteria]." vs ".$entry_data[$idx] .";\n" );
                    }
                }
                
            }
            if (!($is_compatible)){        
                   $to_be_ignored=False;
                   foreach ($known_incompatibilities as $ki){
                       if (($ki["type"]=="incompatibility")
                       &&($ki["user-id"]==$regid)
                       &&($ki["criteria"]==$criteria)){
                           //print("To be ignored\n"); 
                           $to_be_ignored=True;
                           $incompatibilities_ignored++;
                       }
                   } //foreach ki
                   if (!($to_be_ignored)){
                        $incompatibilities_found++;
                        $content .= "<P>";                        
                        $content .="$entry_name has made an incompatible choice:"; 
                        $content .= " <BR/>";
                        $content .= $choice_text; 
                        $content .= "<form action='check_registrations.php' method=\"post\">\n";
                        $content .= "<input type=\"hidden\" id=\"type\" name=\"type\" value=\"incompatibility\">\n";
                        $content .= "<input type=\"hidden\" id=\"user-id\" name=\"user-id\" value=\"$regid\">\n";
                        $content .= "<input type=\"hidden\" id=\"criteria\" name=\"criteria\" value=\"". $criteria ."\">\n";
                        $content .= "<input type=\"hidden\" id=\"until\" name=\"until\" value=\"". date('d/m/Y', strtotime("+7 day")) ."\">\n";
                        $content .= "<input type=\"submit\" name=\"submit\" value=\"Ignore for 7 days\">";
                        $content .= "</form>\n";
                        $content .= "<form action='check_registrations.php' method=\"post\">\n";
                        $content .= "<input type=\"hidden\" id=\"type\" name=\"type\" value=\"incompatibility\">\n";
                        $content .= "<input type=\"hidden\" id=\"user-id\" name=\"user-id\" value=\"$regid\">\n";
                        $content .= "<input type=\"hidden\" id=\"criteria\" name=\"criteria\" value=\"". $criteria ."\">\n";
                        $content .= "<input type=\"hidden\" id=\"until\" name=\"until\" value=\"". date('d/m/Y', strtotime("+30 day")) ."\">\n";
                        $content .= "<input type=\"submit\" name=\"submit\" value=\"Ignore for 30 days\">";
                        $content .= "</form>\n";
                        $content .= "<form action='check_registrations.php' method=\"post\">\n";
                        $content .= "<input type=\"hidden\" id=\"type\" name=\"type\" value=\"incompatibility\">\n";
                        $content .= "<input type=\"hidden\" id=\"user-id\" name=\"user-id\" value=\"$regid\">\n";
                        $content .= "<input type=\"hidden\" id=\"criteria\" name=\"criteria\" value=\"". $criteria ."\">\n";
                        $content .= "<input type=\"hidden\" id=\"until\" name=\"until\" value=\"". date('d/m/Y', strtotime("+500 day")) ."\">\n";
                        $content .= "<input type=\"submit\" name=\"submit\" value=\"Ignore for 500 days\">";
                        $content .= "</form>\n";
                        $content .= " <BR/>\n";
                        $content .= " </P>";                        
                   } //not to be ignored
            } //is incompatible

            
            
        } // foreach incompatibility
        
        foreach ($dates_check as $date_to_ckeck){
            $is_incorrect=False;
            /*
            print("Date Field name ");
            print( $date_to_ckeck[0]);
            print("Date Field value ");
            print( $entry_data[array_search($date_to_ckeck[0], $cols_title)]);
            print("len ". strlen($entry_data[array_search($date_to_ckeck[0], $cols_title)]));
            print("---<BR/>");
            */  
            if (strlen($entry_data[array_search($date_to_ckeck[0], $cols_title)])>6) {
                $date_entered=new DateTime($entry_data[array_search($date_to_ckeck[0], $cols_title)]);
                $operator=$date_to_ckeck[1][0];
                if (preg_match('/(\d)/', substr($date_to_ckeck[1],1,1))==1){
                    //print("is date".substr($date_to_ckeck[1],1));
                    $date_to_compare=DateTime::createFromFormat("d/m/Y",substr($date_to_ckeck[1],1));         
                    //print("date");
                    //print("date".$date_to_compare);
                    //print("date".$date_to_compare->format("d/m/Y"));
                } else {
                    //print("is field".substr($date_to_ckeck[1],1));
                    //print($entry_data[array_search(substr($date_to_ckeck[1],1), $cols_title)]);
                    $date_to_compare=new DateTime($entry_data[array_search(substr($date_to_ckeck[1],1), $cols_title)]);
                    //print("date");
                    //print("date".$date_to_compare->format("d/m/Y"));
                    
                } 
                //print("<BR>");
                if (($operator==">")&&($date_entered<=$date_to_compare)){
                    $is_incorrect=True;
                }
                if (($operator=="<")&&($date_entered>=$date_to_compare)){
                    $is_incorrect=True;
                }
                /*
                if ($is_incorrect){
                    print("incorrect date".$regid."\n");
                }
                */
                if ($is_incorrect){
                    $to_be_ignored=False;
                    foreach ($known_incompatibilities as $ki){
                        /*
                        print("regid: ".$ki["user-id"]."=?=".$regid."\n");
                        if ($ki["user-id"]==$regid){
                            print("Yes\n");
                        } else {
                            print("No\n");
                        }
                        print_r($date_to_ckeck."\n");
                        print($ki["criteria"]."=?=".$date_to_ckeck[0]."\n");
                        */
                        if (($ki["type"]=="date")
                        &&($ki["user-id"]==$regid)
                        &&($ki["criteria"]==$date_to_ckeck[0])){
                            //print("To be ignored \n");
                            $to_be_ignored=True;
                            $incompatibilities_ignored++;
                        }
                    } //foreach ki
                    if (!($to_be_ignored)){
                        $incompatibilities_found++;
                        $content .= " <P>";                        
                        $content .="$entry_name has entered an incompatible date: \n"; 
                        $content .= " <BR/>";
                        $content .= "Field ". $date_to_ckeck[0] ." = ".$entry_data[array_search($date_to_ckeck[0], $cols_title)]; 
                        $content .= " should be ".$operator." ".$date_to_compare->format("d/m/Y")."\n";                    
                        $content .= "<form action='check_registrations.php' method=\"post\">\n";
                        $content .= "<input type=\"hidden\" id=\"type\" name=\"type\" value=\"date\">\n";
                        $content .= "<input type=\"hidden\" id=\"user-id\" name=\"user-id\" value=\"$regid\">\n";
                        $content .= "<input type=\"hidden\" id=\"criteria\" name=\"criteria\" value=\"". $date_to_ckeck[0] ."\">\n";
                        $content .= "<input type=\"hidden\" id=\"until\" name=\"until\" value=\"". date('d/m/Y', strtotime("+7 day")) ."\">\n";
                        $content .= "<input type=\"submit\" name=\"submit\" value=\"Ignore for 7 days\">";
                        $content .= "</form>\n";
                        $content .= "<form action='check_registrations.php' method=\"post\">\n";
                        $content .= "<input type=\"hidden\" id=\"type\" name=\"type\" value=\"date\">\n";
                        $content .= "<input type=\"hidden\" id=\"user-id\" name=\"user-id\" value=\"$regid\">\n";
                        $content .= "<input type=\"hidden\" id=\"criteria\" name=\"criteria\" value=\"". $date_to_ckeck[0] ."\">\n";
                        $content .= "<input type=\"hidden\" id=\"until\" name=\"until\" value=\"". date('d/m/Y', strtotime("+30 day")) ."\">\n";
                        $content .= "<input type=\"submit\" value=\"Ignore for 30 days\">";
                        $content .= "</form>\n";
                        $content .= "<form action='check_registrations.php' method=\"post\">\n";
                        $content .= "<input type=\"hidden\" id=\"type\" name=\"type\" value=\"date\">\n";
                        $content .= "<input type=\"hidden\" id=\"user-id\" name=\"user-id\" value=\"$regid\">\n";
                        $content .= "<input type=\"hidden\" id=\"criteria\" name=\"criteria\" value=\"". $date_to_ckeck[0] ."\"\n";
                        $content .= "<input type=\"hidden\" id=\"until\" name=\"until\" value=\"". date('d/m/Y', strtotime("+500 day")) ."\">\n";
                        $content .= "<input type=\"submit\" value=\"Ignore for 500 days\">";
                        $content .= "</form>\n";
                        $content .= " <BR/>\n";
                        $content .= " </P>";                        
                    } //not to be ignored
                } //is incorrect
            }// if date length >2
        }
    } // if not withdrawn
    /*
    //var_dump($entry_matches);
    echo "<BR/>";
    echo $entry_matches[1][$idx];
    echo var_dump($entry_matches[1][$idx]);
    echo "<BR/>";
    $content .= $entry_matches[1][$idx] ."<BR/>";
    
    echo "********";    
    */
} //for each entry


$content .= "\n $incompatibilities_found incompatibilities found (and not ignored).<BR/>\n";
$content .= "\n $incompatibilities_ignored incompatibilities ignored (already processed).<BR/>\n";
$content .= "\n<HR>\n";
$content .= "Conditions checked: <BR/>\n";
foreach ($incompatibilities as $incompatibility){
    $choice_text = "These answers are not compatible:\n ";
    foreach (array_keys($incompatibility) as $criteria){
        $choice_text .= "$criteria = ";
        $choice_text .= $incompatibility[$criteria] . " --- ";                         
    }
    $content .= $choice_text . "<BR/>";
}
$content .= " </BR>";

$content .= "<HR>". "Dates checked: <BR/>";
foreach ($dates_check as $date_to_ckeck){
    $content .=  $date_to_ckeck[0];
    $operator=substr($date_to_ckeck[1],0,1);
    $content .= " must be ".$operator. " ";  
    
    if (preg_match('#?[[:digit:]]#', substr($date_to_ckeck[1],1) )) {        
        //print("is date");
        $content .=  $date_to_compare->format("d/m/Y");    
    } else {
        //print("is field");
        $content .= substr($date_to_ckeck[1],1);    
    } 
    $content .= " </BR>";
    
} //foreach dates_check
$content .= " </BR>";


$content .= "<HR>". "Columns names: ";
foreach($cols_title as $col){
    $content .=  $col ."; \n ";
}
//array_push($cols_title,"hello");
$content .= " </BR>";


$now =time();
$now_show=date('H:i d/m/Y');
$T->set( 'content', $content );
$T->set( 'now', $now_show );
$T->set( 'incompatible', $incompatibilities_found  );
$T->set( 'ignored', $incompatibilities_ignored );
$T->set( 'total_registrations', $total_registrations );

echo $T->get();

?>