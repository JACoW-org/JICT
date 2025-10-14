<?php

/* by Nicolas Delerue

2025.09.03 Creation

*/

$incompatibilities = [
[ "Lunch box - Friday" => "Yes" ,"Lunch box - Tuesday" => "Yes"  ],
[ "Lunch box - Friday" => "No" , "Visit to SOLEIL in Paris area" => "Yes" ],
[ " I like to reserve a bus from Roissy Charles de Gaulle (CDG) or Orly (ORY) to Deauville on Saturday 16th May" => "Yes" , " I like to reserve a bus from Roissy Charles de Gaulle (CDG) or Orly (ORY) to Deauville on Sunday 17th May"=> "Yes"],
[ "Visit to GANIL in Caen"=> "Yes", "Visit to SOLEIL in Paris area"=> "Yes"], 
[ "Visit to GANIL in Caen"=> "Yes", "Visit to ESRF in Grenoble area"=> "Yes"], 
[ " Visit to SOLEIL in Paris area"=> "Yes", "Visit to ESRF in Grenoble area"=> "Yes"], 
] ;

$dates_check = [
[ "Arrival date" , "<22/5/2026"],
[ "Departure date" , ">17/5/2026"], 
[ "Arrival date" , "<Departure date"],
[ "Passport expiration date" , ">01/07/2026" ]
];

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
$content ="<BR/> <BR/> <BR/>";


$req =$Indico->request( "/event/95/manage/registration/100/registrations/customize", 'GET', false, array(  'return_data' =>true, 'quiet' =>true ) );

$matches = null;
$returnValue = preg_match_all("#data-id=\"(.*)\">#", $req["html"] , $matches);

/*
echo "<BR/>here matches 2<BR/>"; 
var_dump($returnValue);
var_dump($matches[1]);
*/

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
/*
echo "visible_items";
echo $visible_items;
echo "<BR/>";
*/

$req =$Indico->request( "/event/95/manage/registration/100/registrations/customize", 'POST', array(  'visible_items' =>  $visible_items ) , array(  'return_data' =>true, 'quiet' =>true));
//var_dump($req);

$header_matches=null;
$returnValue = preg_match_all('#<th class="i-table" data-sorter="text">(.*)</th>#', $req["html"] , $header_matches);
$cols_title=$header_matches[1];
/*
echo "<BR/>";
echo "<BR/>";
echo "<BR/>";
echo "<BR/>";
echo "<BR/>";
echo "<BR/>";
echo "<BR/>";
echo "<BR/>";
echo "dump"; 
var_dump($header_matches);
echo "dumped"; 
echo "<BR/>";
echo "dump"; 
var_dump($header_matches[1]);
echo "dumped"; 
echo "<BR/>";
var_dump($cols_title);
echo "<BR/>";
*/
/*
echo "header";
echo "<BR/>";

var_dump($header_matches[1]);
echo "<BR/>";
var_dump($header_matches[0]);
echo "<BR/>";
*/
/*
$cols_title=[];

foreach( $header_matches[0] as $col){
echo "col ";
var_dump($col);
echo "<BR/>";
array_push($cols_title,$col[0]);
}
*/

$content .= "Checking for incompatibilities: ";
$content .= " </BR>";

$start_matches = null;
$returnValue = preg_match_all('#<tr id="registration-([[:alnum:]]*)"#', $req["html"] , $start_matches , PREG_OFFSET_CAPTURE );

/*
echo "<BR/>here matches 3d<BR/>"; 
var_dump($returnValue);
echo "<BR/>here matches 3d<BR/>"; 
var_dump($matches);
*/


$end_matches=null;
$returnValue = preg_match_all('#</tr>#', $req["html"] , $end_matches , PREG_OFFSET_CAPTURE );
/*
echo "<BR/>here matches 4<BR/>"; 
var_dump($returnValue);
echo "<BR/>here matches 4<BR/>"; 
var_dump($end_matches);


//var_dump($start_matches[1]);
//var_dump($end_matches[1]);
echo "<BR/>here loop<BR/>"; 
*/
/*
echo "<BR/>here loop<BR/>"; 
echo "<BR/>here loop<BR/>"; 
echo "<BR/>here loop<BR/>"; 
echo "<BR/>here loop<BR/>"; 
echo "<BR/>here loop<BR/>"; 
echo "<BR/>here loop<BR/>"; 
*/

for ($imatch=0;$imatch<count($start_matches[1]);$imatch++){
    $this_entry=substr($req["html"],$start_matches[1][$imatch][1],$end_matches[0][$imatch+1][1]-$start_matches[1][$imatch][1]);
    $entry_matches=null;
    $returnValue = preg_match_all('#<td class=\"i-table\"(.*)>#', $this_entry , $entry_matches );   
    $entry_data=$entry_matches[1];
    array_shift($entry_data);
    array_shift($entry_data);
    array_shift($entry_data);
    for($icol=0;$icol<count($entry_data);$icol++){
        $entry_data[$icol]=trim(preg_replace("/ data-text=\"(.*)\"/", '\1',$entry_data[$icol]));
    }
    $entry_name =  " $imatch ". $entry_data[array_search("First Name", $cols_title)]." " .  $entry_data[array_search("Last Name", $cols_title)] ;
    $show_all_answers=False;
    if ($show_all_answers){
        for ($ival=0;$ival<count($entry_data);$ival++){
            echo $cols_title[$ival]." - ".$entry_data[$ival]." <BR/>"; 
        }
    }
    //Checking incomptibilities
    foreach ($incompatibilities as $incompatibility){
        $is_compatible=False;
        //var_dump($incompatibility);
        //var_dump(array_keys($incompatibility));
        $choice_text = "";        
        foreach (array_keys($incompatibility) as $criteria){
            //var_dump($incompatibility[$criteria]);
            //var_dump($criteria);
            $choice_text .= "$criteria = ";
            $idx=array_search($criteria, $cols_title);
            //$choice_text .= $idx ."  ". $entry_data[$idx] ." ". $incompatibility[$criteria] . "<BR/>";             
            $choice_text .= $entry_data[$idx] . "<BR/>";             
            if (!($entry_data[$idx]==$incompatibility[$criteria])){
                $is_compatible=True;
            }
            
        }
        //$content.= $choice_text;
        
        //$content .= $is_compatible." </BR>";
        //$content .= " </BR>";
        if (!($is_compatible)){        
            $content .="$entry_name has made an incompatible choice:"; 
            $content .= " </BR>";
            $content .= $choice_text; 
            $content .= " </BR>";
        }
        
        
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
            if ($is_incorrect){
                $content .="$entry_name has entered an incompatible date: "; 
                $content .= " </BR>";
                $content .= "Field ". $date_to_ckeck[0] ." = ".$entry_data[array_search($date_to_ckeck[0], $cols_title)]; 
                $content .= " should be ".$operator." ".$date_to_compare->format("d/m/Y");
                $content .= " </BR>";
                $content .= " </BR>";
            }
        }// if date length >2
    }
    
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

$content .= "<HR>". "Conditions checked: <BR/>";
foreach ($incompatibilities as $incompatibility){
    $choice_text = "These answers are not compatible: ";        
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


$done_n =0;
$todo_n =0;
$undone_n =0;
$now =time();

$T->set( 'content', $content );
$T->set( 'todo_n', $todo_n );
$T->set( 'done_n', $done_n );
$T->set( 'undone_n', $undone_n );
$T->set( 'all_n', $todo_n +$done_n );

echo $T->get();

?>