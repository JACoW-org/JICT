<?php

/* by Stefano.Deiuri@Elettra.Eu

2024.11.21 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'abstracts', false, false );
$cfg['verbose'] =0;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

$Indico->load();

$T =new TMPL( $cfg['template'] );
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

$mode =false;
if (!empty($_GET['show_changes'])) $mode ='show_changes';

switch ($mode) {
    case 'show_changes';
        $sort ="[5, 'desc']";
        break;

    default:
        $sort ="[0, 'desc']";
}

$rows_metadata =[];
$rows =false;
$i =0;
foreach ($Indico->data['abstracts_sub'] as $acode =>$abs) {
    if (empty($abs['withdrawn'])) {
        $abs_url =sprintf( "https://indico.jacow.org/event/%d/manage/abstracts/%d/", $cfg['indico_event_id'], $acode );

        $rows[$i] =[
            'Abstract_ID' =>sprintf( "<a href='%s' target='blank'>%s</a>", $abs_url, $acode ),
            'Submitted_Type' =>$abs['stype'],
            'Title' =>false,
            'Content' =>false,
            'Submit_Date' =>date( 'Y-m-d H:i', $abs['ts0'] ),
            'Update_Date' =>($abs['ts'] != $abs['ts0'] ? date( 'Y-m-d H:i', $abs['ts'] ) : false),
            ];
            
        foreach (['title','content'] as $var) {
            $warn =$var == 'content' && strlen($abs[$var]) < 128;

            if ($warn) $rows_metadata[$i] =[ 'class' =>'warn' ];

            $c =empty($abs[$var.'_bak']) || empty($_GET['show_changes']) ? $abs[$var] : htmlDiff( $abs[$var], $abs[$var.'_bak'] );

            $rows[$i][ucwords($var)] =""
                .($warn ? "<b style='font-size: 80%; color: red; background: yellow;'>[warn: short abstract]</b><br />" : false)
                .$c;
                // .$abs[$var] 
                // .(!empty($abs[$var.'_bak']) ? sprintf( "<p style='font-size: 80%%; color: green;'><b style='background: yellow;'>[previous_version]</b><br />%s</p>", $abs[$var.'_bak'] ) : false);
        }

        $i ++;
    }
}


$filters =[
    'Default' =>false,
    'Show changes' =>'?show_changes=1'
    ];

//if (!empty($user['admin'])) $filters['pcode'] ='?pcode=x';

$content =false;
foreach ($filters as $label =>$x) {
    $content .=($content ? ' | ' : 'Mode: ') .sprintf( "<a href='index.php%s'>%s</a>", $x, $label );
}

if (empty($rows)) {
    $T->set( 'content', $content );
    echo $T->get();
    return;
}


$thead ="<tr>"
    ."<th>" .strtr( implode( "</th><th>", array_keys( $rows[0] ) ), '_', ' ' ) ."</th></tr>\n";

$content .="<table id='papers' class='cell-border'>
<thead>
$thead
<thead>
<tbody>
";

$is_admin =!empty($user['admin']);

foreach ($rows as $rid =>$r) {
    $content .="<tr" .(!empty($rows_metadata[$rid]['class']) ? sprintf( " class='%s'", $rows_metadata[$rid]['class'] ) : false) .">"
        ."<td>" .strtr( implode( "</td><td>", array_values( $r ) ), '_', ' ' ) ."</td></tr>\n";
}
$content .="</table>";

$T->set( 'js', "
$(document).ready(function() {
    $('#papers').DataTable({        
        dom: \"<'row'<'col-sm-6'i><'col-sm-6'f>><'row'<'col-sm-12'tr>>\",
        paging: false,
		order: [$sort]
    });
} );
" );

$T->set( 'content', $content );

echo $T->get();

function diff($old, $new){
    foreach($old as $oindex => $ovalue){
            $nkeys = array_keys($new, $ovalue);
            foreach($nkeys as $nindex){
                    $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                            $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                    if($matrix[$oindex][$nindex] > $maxlen){
                            $maxlen = $matrix[$oindex][$nindex];
                            $omax = $oindex + 1 - $maxlen;
                            $nmax = $nindex + 1 - $maxlen;
                    }
            }       
    }
    if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
    return array_merge(
            diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}

function htmlDiff($old, $new){
    $diff = diff(explode(' ', $old), explode(' ', $new));
    foreach($diff as $k){
            if(is_array($k))
                    $ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
                            (!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
            else $ret .= $k . ' ';
    }
    return $ret;
}

?>