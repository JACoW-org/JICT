<?php

/* by Stefano.Deiuri@Elettra.Eu

2024.05.16 - update
2024.05.02 - update for indico 3.3.2
2022.08.22 - 1st version

*/

//date_default_timezone_set( 'US/Central' );

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );


$cfg =config( 'page_papers', false, false );
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

    'title' =>'Revisions',
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
    'scripts' =>"<script src='../html/jquery.sparkline.min.js'></script>\n<script src='../html/chart.min.js'></script>"
    ]);


$content =get_paper_revisions( $_GET['pid'] );

$T->set( 'content', $content );
echo $T->get();
return;



//-----------------------------------------------------------------------------
function get_paper_revisions( $_pid ) {
    global $Indico;

/*     $pedit =$Indico->request( "/event/{id}/api/contributions/$_pid/editing/paper", 'GET', false, 
        [ 'return_data' =>true, 'quiet' =>true, 'cache_time' =>0 ]);

    $pedit['revisions'] =fix_revisions( $pedit['revisions'] ); */

    $pedit =$Indico->get_paper_details( $_pid, 0, true );

    $editor =$pedit['editor']['full_name'];

    $tab =false;
    foreach ($pedit['revisions'] as $r_id =>$r) {
            
        $keys =[ 'id', 'created_dt', 'comment_html', 'tags', 'qa', '#count.files', '#count.tags', 'user.full_name', 'type.name' ];
        
        $row =[];
        $class =false;
        foreach ($keys as $k) {

            if (strpos( $k, '.' )) list( $k1, $k2 ) =explode( '.', $k );
            else {
                $k1 = $k;
                $k2 =false;
            }

            if (empty($k2)) {
                if ($k == 'id') {
                    $row[$k] ="<a href='#r$r_id' style='font-size: 2em'>$r_id</a>" .($r['is_undone'] ? ' (undone)' : false);

                } else if ($k == 'tags') {
                    $tags =false;
                    foreach ($r['tags'] as $t) {
                        $tags .="<span class='tag' style='background:$t[color]' title='$t[title]'>$t[code]</span>";
                    }
                    $row[$k] =$tags;

                } else if (substr( $k, -3 ) == '_dt') {
                    $t =strtotime($r[$k]);
                    $row[$k] =date( 'd/m',$t) .'<br />' .date( 'H:i:s',$t);

                } else $row[$k] =$r[$k];

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

    $pcode =$pedit['contribution']['code'];
    

    $revisions_data =false;
    foreach ($pedit['revisions'] as $rid =>$r) {
        $revisions_data .="<a name='r$rid'><hr /><h4>Revision $rid</h4>" .substr(print_r( $r, true ), 8, -3 );
    }

    unset($pedit['revisions']);

    $paper =$Indico->data['papers'][$pcode];
    foreach ($paper as $var =>$val) {
        if ($val && substr( $var, -3 ) == '_ts') $paper[$var.'-time'] =sprintf( "%d (%s)",  $val, date( 'r',  $val  ));
    }

    return "<br /><h1>$pcode #$_pid</h1>\n"
        ."<a href='#revisions_data'>revisions</a> | <a href='#paper_obj'>paper</a>"
        ."<br /><table>$tab</table><pre>\n\n" 
        ."<a name='revisions_data'><hr /></a><h1>Revisions data</h1>" .print_r($pedit, true)
        .$revisions_data
        ."<a name='paper_obj'><hr /></a><h1>Paper obj</h1>" .print_r($paper, true);
}

?>