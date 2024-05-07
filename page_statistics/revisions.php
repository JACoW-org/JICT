<?php

/* by Stefano.Deiuri@Elettra.Eu

2024.05.02 - update for indico 3.3.2
2022.08.22 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

define( 'FAIL_QA_STRING', 'his revision has failed QA.' );

session_start();

$cfg =config( 'page_statistics', false, false );
$cfg['verbose'] =0;

$Indico =new INDICO( $cfg );
$Indico->load();

$user =$Indico->auth();
if (!$user) exit;

$T =new TMPL( '../template.html' );
$T->set([
    'path' =>'../',
    'style' =>'
    
    main { font-size: 14px; } 
    h1 { color: black; } 
    table { border-collapse: collapse; width: 100%; } 
    table td { border: 1px solid silver; padding: 10px } 
    table th { padding: 10px } 
    td:nth-child(1),td:nth-child(2) { font-size: 10px} 
    table tr.editor td { background: #effb5a } 
    table tr.undone td { background: #fd8989 } 
    span.tag { border: 1px solid #777; border-radius: 3px; padding: 1px 3px 1px 3px; margin-right: 3px; }
    
    ',

    'title' =>$cfg['name'],
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
//     ." " .__h( 'i', "", [ 'class' =>'fa fa-power-off', 'onClick' =>"document.location =\"$_SERVER[PHP_SELF]?cmd=logout\"" ]),
    'scripts' =>"<script src='../html/jquery.sparkline.min.js'></script>\n<script src='../html/chart.min.js'></script>"
    ]);


$content .=get_paper_revisions( $_GET['pid'] );

$T->set( 'content', $content );
echo $T->get();
return;

//-----------------------------------------------------------------------------
function get_paper_revisions( $_pid ) {
    global $Indico;

    $pedit =$Indico->request( "/event/{id}/api/contributions/$_pid/editing/paper", 'GET', false, 
        [ 'return_data' =>true, 'quiet' =>true, 'cache_time' =>0 ]);

    $editor =$pedit['editor']['full_name'];

    $tab =false;
    foreach ($pedit['revisions'] as $r_id =>$r) {
            
        $keys =[ 'id', 'created_dt', 'comment_html', 'tags', '#count.files', '#count.tags', 'user.full_name', 'type.name' ];
        
        $row =[];
        $class =false;
        foreach ($keys as $k) {

            list( $k1, $k2 ) =explode( '.', $k );

            if (empty($k2)) {
                if ($k == 'id') {
                    $row[$k] =$r_id .($r['is_undone'] ? ' (undone)' : false);

                } else if ($k == 'tags') {
                    $tags =false;
                    foreach ($r['tags'] as $t) {
                        $tags .="<span class='tag' style='background:$t[color]' title='$t[title]'>$t[code]</span>";
                    }
                    $row[$k] =$tags;

                } else if ($k == 'comments') {
                    $v =false;
                    foreach ($r[$k] as $t) {
                        if (strpos( $t['text'], FAIL_QA_STRING )) $v .="<p style='font-weight: bold; color: red'>$t[text]</p>";
                        else $v .="<p>$t[text]</p>";
                    }
                    $row[$k] =$v;

                } else if (substr( $k, -3 ) == '_dt') $row[$k] =str_replace( "T", "<br />", substr( $r[$k], 5, 14 ));
                else $row[$k] =$r[$k];

            } else if ($k1 == '#count') {
                $row[$k] =count($r[$k2]);

            } else if ($k == 'user.full_name') {
                $row[$k] =$r[$k1][$k2];
                if ($r['is_editor_revision']) {
                    $row[$k] .=" (editor)";
                    $class ='editor';
                }

            } else {
                $row[$k] =$r[$k1][$k2];
            }

            if ($r['is_undone']) $class ='undone';
        }
        
        if (!$tab) {
            $tag ='th'; $tab .="<tr><$tag>" .implode( "</$tag><$tag>", array_keys($row) ) ."</$tag></tr>\n";
        }

        $tag ='td'; $tab .=str_replace( $editor, "<b>$editor</b>", "<tr" .($class ? " class='$class'" : false) ."><$tag>" .implode( "</$tag><$tag>", $row ) ."</$tag></tr>\n" );
    }

    $cdt =str_replace( "T", "<br />", substr( $r['created_dt'], 5, 14 ));
    $tab =str_replace( $cdt, "<b style='color:red'>$cdt</b>", $tab );

    return "<br /><h1>" .$pedit['contribution']['code'] ." #$_pid</h1>\n<table>$tab</table><pre>\n\n" .print_r($pedit, true);
}

?>