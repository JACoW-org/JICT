<?php

/* by Stefano.Deiuri@Elettra.Eu

2025.06.03 - new workflow, the papers are assigned
2025.05.30 - show warning for authors instead of ok
2025.05.29 - show ok authors
2024.05.17 - update
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


$show =$_GET['show'] ?? false;
$selected_paper =false;

if (!empty($_GET['action'])) {
    $pcode =strtoupper( $_GET['pcode'] );

    if (!empty($Indico->data['data'][$pcode])) {
        file_write( $cfg['logs_path'] .'/authors_check-activity.log', date('r') ." | $user[full_name] | $_GET[action] | $pcode\n", 'a' );

        switch ($_GET['action']) {
            case 'get':
                $show ='paper';
                $selected_paper =$pcode;

                if ($Indico->data['data'][$pcode]['assigned_to'] == $user['full_name']) break;

                $Indico->data['data'][$pcode]['assigned_to'] =$user['full_name'];
                $Indico->data['data'][$pcode]['assigned_ts'] =time();

                $Indico->save_file( 'data', 'out_data', 'DATA', [ 'save_empty' =>true ]);
                break;

            case 'unassign':
                $Indico->data['data'][$pcode]['assigned_to'] =false;
                $Indico->data['data'][$pcode]['assigned_ts'] =false;
                $Indico->save_file( 'data', 'out_data', 'DATA', [ 'save_empty' =>true ]);
                $show ='mine';
                break;

            case 'refresh':
                $p =$Indico->data['papers'][$pcode];

                $c =$Indico->request( "/event/{id}/contributions/$p[id].json", 'GET', false, 
                    [ 'return_data' =>true, 'quiet' =>true, 'cache_time' =>0 ]);

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
                    'authors' =>$authors,
                    // '_debug' =>$c
                    ];
            
                echo json_encode($obj);
                return;
            
            case 'done':
                $Indico->data['data'][$pcode]['done'] =true;
                $Indico->data['data'][$pcode]['done_ts'] =time();
                $Indico->data['data'][$pcode]['done_date'] =date( 'r' );
                $Indico->data['data'][$pcode]['done_author'] =$user['full_name'];

                $Indico->save_file( 'data', 'out_data', 'DATA', [ 'save_empty' =>true ]);

                $show ='mine';
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
$assigned_n =0;
$my_n =0;
$now =time();

$content =false;

if (!empty($Indico->data['data'])) ksort( $Indico->data['data'] );

if ($show == 'todo2') $content .="<button class='action' onClick='document.location=\"$_SERVER[PHP_SELF]?show=$show&print=1\"'> PRINT </button><br /><br />";

foreach ($Indico->data['data'] as $pcode =>$x) {
    if (!empty($Indico->data['papers'][$pcode])) {
        $p =$Indico->data['papers'][$pcode];
    
        if ($p[$cfg['filter']['key']] == $cfg['filter']['value'] && empty($p['hide'])) {
            $warning =false;
            $skip =false;
        
            $authors =false;
            foreach ($p['authors_by_inst'] as $inst =>$a) {
                if (empty($inst)) {
                    $inst ="<b style='color: red'>NO AFFILIATION</b>";
                    $warning =true;
                }
    
                if (!empty($x['pdf_authors_list'])) {
                    $inst_authors =false;
                    foreach ($a as $sa) {
                        $sa_parts =explode( '.', $sa );
                        if (!empty($sa_parts)) $sa_so =strtolower(trim(end($sa_parts)));

                        if (in_array( str_replace('-'," ",$sa), $x['pdf_authors_list'])) $sa ="<author_ok>$sa</author_ok>";
                        else if (in_array( $sa_so, $x['pdf_authors_list'])) $sa ="<author_ok2>$sa</author_ok2>";
                        else $sa ="<author_warn>$sa</author_warn>";

                        $inst_authors .=($inst_authors ? ', ' : false) .$sa .(!empty($_GET['debug']) ? " ($sa_so)" : false);
                    }

                    $authors .=($authors ? "<br />" : false) .$inst_authors ." - <inst>$inst</inst>";

                } else {
                    $authors .=($authors ? "<br />" : false) ."<author_warn>".implode( ', ', $a ) ."</author_warn> - <inst>$inst</inst>";
                }
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
            $row_type =false;

            switch ($show) {
                case 'mine':
                    if ($x['assigned_to'] == $user['full_name'] && empty($x['done'])) $row_type ='todo2';
                    break;

                case 'paper':
                    if ($selected_paper == $pcode) $row_type ='paper';
                    break;

                case 'todo':
                case 'todo2':
                    //$content .="<button onClick='$_SERVER[PHP_SELF]?show=$show&print=1'> PRINT </button>";

                    if (empty($x['done']) && empty($x['assigned_to'])) $row_type =$show;
                    break;

                case 'assigned':
                    if (empty($x['done']) && !empty($x['assigned_to'])) $row_type ='todo2';
                    break;
 
                case 'done':
                    if (!empty($x['done'])) $row_type =$show;
                    break; 

                default:
                    if (empty($x['done'])) $row_type =$show;
                    break; 
            }

            // calc numbers
            if (empty($x['done'])) {
                if (empty($x['assigned_to'])) $todo_n ++;
                else {
                    if ($x['assigned_to'] == $user['full_name']) $mine_n ++;
                    $assigned_n ++;

                    // if (me()) echo "$pcode ($p[status]/$p[status_qa]) $x[assigned_to] $x[done]\n";
                } 

            } else {
                $done_n ++;
            }  

            $common ="<code><a href='https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$x[id]' target='paper'>$pcode</a></code>
                    <editor>$p[editor]</editor>
                    <div class='title'>$title</div>
                    <authors>$authors</authors>";

            $extraclass =false;
            switch($row_type) {
                case 'done':
                    $row ="
                    <div id='paper_$pcode' class='paper no-print done'>"
                    .($user['admin'] || true ? "<button id='undone_$pcode' class='undone' onClick='undone(\"$pcode\")'> UN-DONE </button>" : false)
                    ."<doneinfo>" .date('r', $x['done_ts']) ." - $x[done_author]</doneinfo>
                    $common
                    <img src='images/$pcode.jpg?$now' width='700px'/>
                    </div>\n";
                    break;

                case 'paper':
                    $extraclass =' onepaper';
                case 'todo':
                    $row ="
                    <div id='paper_$pcode' class='paper$extraclass' onMouseOver='show_buttons(\"$pcode\",1)' onMouseOut='show_buttons(\"$pcode\",0)'>
                    <button id='refresh_$pcode' class='refresh' onClick='refresh(\"$pcode\")'>REFRESH</button>
                    <button id='done_$pcode' class='done' onClick='done(\"$pcode\")'> DONE </button>
                    $common
                    <img src='images/$pcode.jpg?$now' width='700px'/>
                    </div>\n";
                    break;

                case 'todo2':
                    $select_info =empty($x['assigned_to']) || $show == 'mine' ? false : sprintf( " <small>(assigned to %s)</small>", $x['assigned_to'] );

                    if (!empty($_GET['print'])) $common .="<img src='images/$pcode.jpg?$now' width='700px'/>";

                    $row ="
                    <div id='paper_$pcode' class='paper' onMouseOver='show_buttons(\"$pcode\",1)' onMouseOut='show_buttons(\"$pcode\",0)'>
                    <button id='refresh_$pcode' class='refresh' onClick='refresh(\"$pcode\")'>REFRESH</button>
                    <button id='done_$pcode' class='select' onClick='action_get(\"$pcode\")'> SELECT $select_info</button>
                    $common
                    <br  />
                    </div>\n";
                    break;
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

$T->set( 'content', $content );
$T->set( 'mine_n', $mine_n ?? '0' );
$T->set( 'todo_n', $todo_n ?? '0' );
$T->set( 'done_n', $done_n ?? '0' );
$T->set( 'assigned_n', $assigned_n ?? '0' );
$T->set( 'undone_n', $undone_n ?? '0' );
$T->set( 'all_n', $todo_n +$done_n +$assigned_n );

echo $T->get();

?>