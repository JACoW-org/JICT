<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.04.05 - update (bottom navbar)
2023.04.03 - update (auth & template)
2023.03.03 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_slides' );
$cfg['verbose'] =0;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

$Indico->load();


if ($_GET['download']) {
    $source_file_type_id =false;
    $pdf_file_type_id =false;
    $types =$Indico->request( '/event/{id}/editing/api/slides/file-types', 'GET', false, 
        [ 'return_data' =>true, 'cache_time' =>86400, 'quiet' =>false ]);

    foreach ($types as $x) {
        if (substr(strtolower($x['name']),0,6) == 'source') $source_file_type_id =$x['id'];
        else if (strtolower($x['name']) == 'pdf') $pdf_file_type_id =$x['id'];
    } 

    $pid =$_GET['pid'];
    $slide =$Indico->request( "/event/{id}/api/contributions/$pid/editing/slides", 'GET', false, [ 'return_data' =>true ]);
    $revision =end($slide['revisions']);

    foreach ([ $source_file_type_id, $pdf_file_type_id ] as $file_type) {
        foreach ($revision['files'] as $f) {
    //        if (strpos( $f['filename'], '_talk' )) {
            if ($f['file_type'] == $file_type) {
                $ext =end(explode( '.', $f['filename'] ));

                $fname ="$_GET[download].$ext";

                $cmd =sprintf( "wget -q -O tmp/%s --header='Authorization: Bearer %s' %s", $fname, $Indico->cfg['indico_token'], $f['external_download_url'] );
                system( $cmd );
                download_file( "tmp/$fname", $fname, false );
                unlink( "tmp/$fname" );
                return;
            }
        }
    }

    echo "no files found!";
    return;
}


$T =new TMPL( $cfg['template'] );
$T->set([
    'style' =>'main { font-size: 14px; margin-bottom: 6em; }',
    'title' =>'Slides',
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
    'path' =>'../',
    'head' =>"<link rel='stylesheet' type='text/css' href='../html/datatables.min.css' />
    <link rel='stylesheet' type='text/css' href='../page_edots/colors.css' />
    <link rel='stylesheet' type='text/css' href='style.css?20230508' />",
    'scripts' =>"<script src='../html/datatables.min.js'></script>",
    'js' =>false
    ]);


$status =&$Indico->data['status'];
//print_r( $status );


// https://indico.jacow.org/event/41/editing/api/slides/list


$status_key =$Indico->request( '/event/{id}/editing/api/slides/list' );
//print_r( $Indico->data[$status_key] );

/* 
$status =[];
foreach ($Indico->data[$status_key] as $x) {
    $status[$x['code']] =empty($x['editable']) ? false : $x['editable']['state'];
}
 */

$conf_day =$_GET['day'];


if ($_GET['ok']) {
    $pcode =$_GET['ok'];
    
    $status[$pcode] =[
        'ts' =>time()
    ];
    
    $Indico->save_file( 'status', 'out_status', 'STATUS', [ 'save_empty' =>true ]);
    header( "location: $_SERVER[PHP_SELF]?day=$conf_day" );
    return;
}

//$content ="<table class='days'>\n<tr>\n";

if (empty($conf_day)) {
    $day =date( 'Y-m-d' );
    if (!empty($Indico->data['programme']['days'][$day])) {
        header( "location: $_SERVER[PHP_SELF]?day=$day" );
        return;
    }
}

$stats =false;
$day_id =1;
$day_active =false;
foreach ($Indico->data['programme']['days'] as $day =>$x) {
    $day_talks =0;
    $day_ok =0;

    foreach ($x as $s) {
        if (empty($s['poster_session'])) {
            foreach ($s['papers'] as $pcode =>$p) {
                $day_talks ++;
                if (!empty($status[$pcode])) $day_ok ++;
            }
        }
    }

    if ($day_talks) {
        $day_status =ceil( $day_ok *100 /$day_talks );

        $wday =date( 'l', strtotime( $day ));
    
//        $cls =$day == $conf_day ? 'active' : false;
    
        if ($day == $conf_day) $day_active =$day_id;

        if ($day_status == 100) $status_bar ="<center><div class='status_bar_completed' title='talks verified' style='width: 100%'>completed</div></center>";
        else $status_bar =$day_ok ? "<center><div class='status_bar' title='talks verified' style='width: $day_status%'>$day_status%</div></center>" : "<div class='status_bar'style='width: 0'>&nbsp;</div>";

  //      $content .=sprintf( "<td class='%s'><a href='%s?day=%s'>%s, %s</a>%s</td>\n", $cls, $_SERVER['PHP_SELF'], $day, $day, $wday, $status_bar );

        $T->set( "day$day_id", sprintf( "<a href='%s?day=%s'>%s, %s</a>%s", $_SERVER['PHP_SELF'], $day, $day, $wday, $status_bar ));

        $day_id ++;
    }
}

//$content .="</tr>\n</table>\n\n";

$content =false;

if (empty($conf_day) || empty($Indico->data['programme']['days'][$conf_day])) {
    $T->set( 'content', $content );
    echo $T->get();
    return;
}


$papers_id =[];

$day_ok =0;
$i =1;

$rows =false;
foreach ($Indico->data['programme']['days'][$conf_day] as $sid =>$s) {
    if (empty($s['poster_session'])) {
        foreach ($s['papers'] as $pcode =>$p) {
            $papers_id[$pcode] =$p['id'];

            list( $presenter, $affiliation ) =explode( ' - ', $p['presenter'] );

            $fname =strtr(sprintf( '%02d-%s-%s', $i, $pcode, $presenter ), ' ', '_' );

            $rows[$i] =[
                'Order' =>empty($status[$pcode]) ? sprintf( "<a href='%s?pid=%d&download=%s'>%02d</a>", $_SERVER['PHP_SELF'], $p['id'], $fname, $i ) : sprintf( '%02d', $i ),
                'Time' =>$p['time_from'],
                'Code' =>$pcode,
                'Room' =>$s['room'],
                'Type' =>$s['type'],
                'Title' =>$p['title'],
                'Presenter' =>$p['presenter']
                ];

//            if ($_GET['dev'] && empty($status[$pcode])) $rows[$i]['Order'] =sprintf( "<a href='%s?pid=%d&download=%s'>%02d</a>", $_SERVER['PHP_SELF'], $p['id'], $fname, $i );

            $i ++;

            if (!empty($status[$pcode])) $day_ok ++;
        }
    }
}

$day_status =ceil( count($rows)*100/$day_ok );

$thead .="<tr><th>" .strtr( implode( "</th><th>", array_keys( $rows[1] )), '_', ' ' ) ."</th></tr>\n";

$content .="<div style='margin-bottom: 1em;'></div>
<table id='talks' class='cell-border'>
<thead>
$thead
<thead>
<tbody>
";

foreach ($rows as $r) {
    $pid =$papers_id[ $r['Code'] ];

    $contribution_url ="https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$pid";
    $paper_url ="https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$pid/editing/slides";
    $ok_url ="$_SERVER[PHP_SELF]?day=$conf_day&ok=$r[Code]";

    if (empty($status[$r['Code']])) {
        $r['Presenter'] =sprintf( "%s [ <a href='%s'>OK</a> ]", $r['Presenter'], $ok_url );
        $cls =false;

    } else {
        $cls ='ok';
    }

    $r['Code'] =sprintf( "<a href='%s' target='_blank'>%s</a>", $paper_url, $r['Code'] );

    $r['Title'] =sprintf( "<a href='%s' target='_blank'>%s</a>", $contribution_url, $r['Title'] );
    
    $content .="<tr class='$cls'><td>" .implode( "</td><td>", array_values( $r )) ."</td></tr>\n";
}
$content .="</table>";


$T->set( 'js', "
$(document).ready(function() {
    $('#talks').DataTable({
        dom: \"<'row'<'col-sm-6'i><'col-sm-6'f>><'row'<'col-sm-12'tr>>\",
        paging: false,
		order: [[0, 'asc']]
    });

    $('#nav_day$day_active').addClass( 'active' );
} );
" );

$T->set( 'content', $content );

echo $T->get();

?>