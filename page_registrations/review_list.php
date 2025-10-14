<?php

/* by Stefano.Deiuri@Elettra.Eu

2024.05.17 - update
2023.05.11 - update
2022.08.25 - filter function
2022.08.22 - refresh function
2022.08.20 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );



$cfg =config( 'test' );
$cfg['verbose'] =1;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;


$Indico->load();


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

$req =$Indico->request( "/event/{id}/papers/reviewing/", 'GET', false, array( 'return_data' =>true, 'quiet' =>true ) );



$doc = new DOMDocument();
$doc->loadHTML($req);

/*
echo "<BR/>here trl<BR/>"; 
var_dump($doc->getElementById("to-review-list"));
*/

/*
echo "<BR/>here tag<BR/>"; 
var_dump($doc->getElementsByTagName("to-review-list"));
*/
/*
echo "<BR/>here cr<BR/>"; 
var_dump($doc->getElementById("contribution-row"));
*/
/*
echo "<BR/>here cn<BR/>"; 
var_dump($doc->getElementById("to-review-list")->childNodes);
echo "<BR/>here cn1<BR/>"; 
var_dump($doc->getElementById("to-review-list")->childNodes[1]);
echo "<BR/>here cn1<BR/>"; 
var_dump($doc->getElementById("to-review-list")->childNodes[1]->childNodes);
echo "<BR/>here cn1-0<BR/>"; 
var_dump($doc->getElementById("to-review-list")->childNodes[1]->childNodes[0]);
echo "<BR/>here cn1-1<BR/>"; 
var_dump($doc->getElementById("to-review-list")->childNodes[1]->childNodes[1]);
echo "<BR/>here cn1-2<BR/>"; 
var_dump($doc->getElementById("to-review-list")->childNodes[1]->childNodes[2]);
/*
echo "<BR/>here cn0<BR/>"; 
var_dump($doc->getElementById("to-review-list")->childNodes[0]);
*/
/*
echo "<BR/>here<BR/>"; 
var_dump($doc->getElementById("to-review-list")->childNodes[1]->wholeText);
echo "<BR/>here<BR/>"; 

var_dump($doc->getElementById("to-review-list")->childNodes[1]->childNodes);
echo "<BR/>here<BR/>"; 
*/


$content =false;
$content ="";


$iitem=0;
foreach ($doc->getElementById("to-review-list")->childNodes[1]->childNodes as $item) {
    if ($item->nodeName=="div"){
        $content.="<BR><BR>item:".$iitem."</BR>";
        //echo "<BR><BR>item:".$iitem."</BR>";
        //echo $item->nodeName."</BR>";
        $content .=$item->nodeValue."</BR>";
        /*
        var_dump($item);
        echo "<BR/>here cn 0<BR/>";         
        var_dump($item->childNodes[0]);
        var_dump($item->childNodes[0]->childNodes[0]);
        echo "<BR/>here cn1<BR/>";         
        var_dump($item->childNodes[1]);
        echo "<BR/>here cn10<BR/>";         
        var_dump($item->childNodes[1]->childNodes[0]);
        echo "<BR/>here cn11<BR/>";         
        var_dump($item->childNodes[1]->childNodes[1]);
        echo "<BR/>here cn110<BR/>";         
        var_dump($item->childNodes[1]->childNodes[1]->childNodes[0]);
        echo "<BR/>here cn111<BR/>";
        var_dump($item->childNodes[1]->childNodes[1]->childNodes[1]);
        echo "<BR/>here cn111a<BR/>";
        var_dump($item->childNodes[1]->childNodes[1]->childNodes[1]->attributes);
        var_dump($item->childNodes[1]->childNodes[1]->childNodes[1]->attributes[0]);
        var_dump($item->childNodes[1]->childNodes[1]->childNodes[1]->attributes[1]);
        echo "<BR/>here cn1111aV<BR/>";
        var_dump($item->childNodes[1]->childNodes[1]->childNodes[1]->attributes[0]->value);
        echo "<BR/>here cn1110<BR/>";
        var_dump($item->childNodes[1]->childNodes[1]->childNodes[1]->childNodes[0]);
        echo "<BR/>here cn1111<BR/>";
        var_dump($item->childNodes[1]->childNodes[1]->childNodes[1]->childNodes[1]);
        echo "<BR/>here cn12<BR/>";
        var_dump($item->childNodes[1]->childNodes[2]);
        echo "<BR/>here cn2<BR/>";         
        var_dump($item->childNodes[2]);
        echo "<BR/>here<BR/>";         
        var_dump($item->childNodes[3]);
        echo "<BR/>here<BR/>";         
        */
        echo $item->nodeValue."<BR/>";
        if ($item->childNodes[1]->childNodes[1]->childNodes[1]->attributes[0]->name == "href"){
            $link=$item->childNodes[1]->childNodes[1]->childNodes[1]->attributes[0]->value;
            echo "link: ".$link;
        } else {
            $link="";
            echo "No link";

        }
        
        //echo "<BR/>here<BR/>";         
        //echo "<BR/>here<BR/>";   
          
        /*
        echo count($item->nodeValue)."<BR/>";
        echo count(trim($item->nodeValue))."<BR/>";
        echo substr($item->nodeValue,25)."<BR/>";
        */
        /*
        echo substr(trim($item->nodeValue),1, 3)."<BR/>";
        echo strtok(substr(trim($item->nodeValue),1),":")."<BR/>";      
        */
        $contrib_id=explode("/",$link)[4];
        echo "contrib_id:".$contrib_id."<BR/>";
        /*
        $contrib =$Indico->request( "/event/{id}/contributions/". $contrib_id.".json", 'GET', false, array( 'return_data' =>true, 'quiet' =>true ) );        
        echo "contrib: ".'http://indico.jacow.org/event/'.$cfg['indico_event_id']."/contributions/$contrib_id.json <BR/> ";
        //var_dump($contrib);
        //echo "<BR/>here cont<BR/>"; 
        */
        $paper =$Indico->request( "/event/{id}/papers/api/".$contrib_id, 'GET', false, array( 'return_data' =>true, 'quiet' =>true ) );        
        //var_dump($item->nodeName);
        /*
        var_dump($paper);
        echo "<BR><BR> contrib: ";
        var_dump($paper["contribution"]);
        echo "<BR><BR> timeline: ";
        var_dump($paper['last_revision']["timeline"]);
        */
        $content .="<BR> title: ";
        $content .="<A HREF='http://indico.jacow.org/$link'>".$paper["contribution"]["title"]."</A><BR/>";
        $content .="<BR> timeline: ";
        foreach ($paper['last_revision']["timeline"] as $itime) {
            $content .="=>".$itime["created_dt"]." --- ".$itime["text"]."</BR>";        
        }

        $iitem=$iitem+1;
    }
} // foreach

$done_n =$iitem;
$todo_n =0;
$undone_n =0;
$now =time();


/*
$show =$_GET['show'] ?? false;

if (!empty($_GET['action'])) {
    $pcode =strtoupper( $_GET['pcode'] );

    if (!empty($Indico->data['data'][$pcode])) {
        file_write( $cfg['logs_path'] .'/authors_check-activity.log', date('r') ." | $user[full_name] | $_GET[action] | $pcode\n", 'a' );

        switch ($_GET['action']) {
            case 'refresh':
                $p =$Indico->data['papers'][$pcode];

                $c =$Indico->request( "/event/{id}/contributions/$p[id].json", 'GET', false, array( 'return_data' =>true, 'quiet' =>true ) );

                $authors_by_inst =false;
                foreach ($c['persons'] as $author) {
                    if ($author["author_type"] == "primary") $authors_by_inst[$author['affiliation']][] =$Indico->author_name( $author );
                }

                foreach ($c['persons'] as $author) {
                    if ($author["author_type"] == "secondary") $authors_by_inst[$author['affiliation']][] =$Indico->author_name( $author );
                }

                $authors =false;
                foreach ($authors_by_inst as $inst =>$a) {
                    if (empty($inst)) $inst ="<b style='color: red'>NO AFFILIATION</b>";
                    $authors .=($authors ? "<br ?>" : false) .implode( ', ', $a ) .' - ' .$inst;
                }

                $obj =[
                    'pcode' =>$pcode,
                    'title' =>$c['title'],
                    'authors' =>$authors
                    ];
            
                echo json_encode($obj);
                return;
            
            case 'done':
                $Indico->data['data'][$pcode]['done'] =true;
                $Indico->data['data'][$pcode]['done_ts'] =time();
                $Indico->data['data'][$pcode]['done_date'] =date( 'r' );
                $Indico->data['data'][$pcode]['done_author'] =$user['full_name'];

                $Indico->save_file( 'data', 'out_data', 'DATA', [ 'save_empty' =>true ]);
                break;

            case 'undone':
                $Indico->data['data'][$pcode]['done'] =false;
                unset( $Indico->data['data'][$pcode]['done_ts'] ); 
                $Indico->data['data'][$pcode]['undone_date'] =date( 'r' );
                $Indico->data['data'][$pcode]['undone_author'] =$user['full_name'];  

                $Indico->save_file( 'data', 'out_data', 'DATA', [ 'save_empty' =>true ]);
                break;
        }
    }
}
*/



/*
foreach ($Indico->data['data'] as $pcode =>$x) {
    if (!empty($Indico->data['papers'][$pcode])) {
        $p =$Indico->data['papers'][$pcode];
    
        if ($p[$cfg['filter']['key']] == $cfg['filter']['value']) {
            $warning =false;
        
            $authors =false;
            foreach ($p['authors_by_inst'] as $inst =>$a) {
                if (empty($inst)) {
                    $inst ="<b style='color: red'>NO AFFILIATION</b>";
                    $warning =true;
                }
    
                $authors .=($authors ? "<br ?>" : false) .implode( ', ', $a ) .' - ' .$inst;
            }
            
            $tip =explode( ' ', $p['title'] );
            $tpp =explode( ' ', str_replace( array('∗', '*', "\u2217"),  array("","",""), strtolower($x['pdf_title']) ));
        
            $title =false;
            foreach ($tip as $word) {            
                if (!in_array( strtolower($word), $tpp )) {
                    $wrong_word =true;
                    $warning =true;
    
                } else {
                    $wrong_word =false;
                }
    
                $title .=($title ? " " : false) .sprintf( ($wrong_word ? "<wrong>%s</wrong>" : "%s"), $word );
            }
        
            $row =false;
            if (empty($x['done'])) {
                if ($show != 'done') $row ="
                    <div id='paper_$pcode' class='paper' onMouseOver='show_buttons(\"$pcode\",1)' onMouseOut='show_buttons(\"$pcode\",0)'>
                    <button id='refresh_$pcode' class='refresh' onClick='refresh(\"$pcode\")'>Refresh</button>
                    <button id='done_$pcode' class='done' onClick='done(\"$pcode\")'> Done </button>
                    <code><a href='https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$x[id]' target='paper'>$pcode</a></code>
                    <editor>$p[editor]</editor>
                    <div class='title'>$title</div>
                    <authors>$authors</authors>
                    <img src='images/$pcode.jpg?$now' width='700px'/>
                    </div>\n";
    
                $todo_n ++;
    
            } else {
                if ($show != 'todo') $row ="
                    <div id='paper_$pcode' class='paper no-print done'>"
                    .($user['admin'] || true ? "<button id='undone_$pcode' class='undone' onClick='undone(\"$pcode\")'> UnDone </button>" : false)
                    ."<doneinfo>" .date('r', $x['done_ts']) ." - $x[done_author]</doneinfo>
                    <code><a href='https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$x[id]' target='paper'>$pcode</a></code>
                    <editor>$p[editor]</editor>
                    <div class='title'>$title</div>
                    <authors>$authors</authors>
                    <img src='images/$pcode.jpg?$now' width='700px'/>
                    </div>\n";
    
                $done_n ++;
            }
    
            if (!empty($x['undone_date'])) $undone_n ++;
    
            if ($show == 'warn' && !$warning) {
                // skip
            } else {
                $content .=$row;
            }
        }
    }
}

*/

$T->set( 'content', $content );
$T->set( 'todo_n', $todo_n );
$T->set( 'done_n', $done_n );
$T->set( 'undone_n', $undone_n );
$T->set( 'all_n', $todo_n +$done_n );

echo $T->get();

?>