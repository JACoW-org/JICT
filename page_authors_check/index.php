<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.05.11 - update
2022.08.25 - filter function
2022.08.22 - refresh function
2022.08.20 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_authors_check' );
$cfg['verbose'] =0;

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


$show =$_GET['show'];

if (!empty($_GET['action'])) {
    $pcode =strtoupper( $_GET['pcode'] );

    if (!empty($Indico->data['data'][$pcode])) {
        file_write( 'app.log', date('r') ." | $user[full_name] | $_GET[action] | $pcode\n", 'a' );

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

$done_n =0;
$todo_n =0;
$undone_n =0;
$now =time();

foreach ($Indico->data['data'] as $pcode =>$x) {
    $p =$Indico->data['papers'][$pcode];

//    if ($p['status_qa'] == 'QA Approved') {
    if ($p[$cfg['filter']['key']] == $cfg['filter']['value']) {
        $warning =false;

        $e =$Indico->data['edot'][$pcode];
    
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
                <editor>$e[editor]</editor>
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
                <editor>$e[editor]</editor>
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


/* $info ="<div class='row'><div class='col-12 col-md-12 text-center no-print'>
<a href='index.php?show=todo'>todo</a> ($todo_n) |
<a href='index.php?show=done'>done</a> ($done_n) |
<a href='index.php'>all</a>
</div></div>"; */

//$content .=print_r( $user, true );

$T->set( 'content', $info .$content );
$T->set( 'todo_n', $todo_n );
$T->set( 'done_n', $done_n );
$T->set( 'undone_n', $undone_n );
$T->set( 'all_n', $todo_n +$done_n );

echo $T->get();

?>