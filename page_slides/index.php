<?php

/* by Stefano.Deiuri@Elettra.Eu

2024.05.19 - publishing status
2023.04.05 - update (bottom navbar)
2023.04.03 - update (auth & template)
2023.03.03 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_slides' );
$cfg['verbose'] =0;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

$Indico->load();

if (!empty($_GET['download'])) {
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
            if ($f['file_type'] == $file_type) {
                $ext =pathinfo( $f['filename'], PATHINFO_EXTENSION );
                $fname ="$_GET[download].$ext";

                $cmd =sprintf( "wget -q -O %s/%s --header='Authorization: Bearer %s' %s", $cfg['tmp_path'], $fname, $Indico->cfg['indico_token'], $f['external_download_url'] );
                                
                system( $cmd );
                download_file( $cfg['tmp_path'] ."/$fname", $fname, false );
                unlink(  $cfg['tmp_path'] ."/$fname" );
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
    'head' =>"<link rel='stylesheet' type='text/css' href='../dist/datatables/datatables.min.css' />
    <link rel='stylesheet' type='text/css' href='../page_edots/colors.css' />
    <link rel='stylesheet' type='text/css' href='style.css?20240520_1150' />",
    'scripts' =>"<script src='../dist/datatables/datatables.min.js'></script>",
    'js' =>false
    ]);


$status =&$Indico->data['status'];

$status_key =$Indico->request( '/event/{id}/editing/api/slides/list' );

$status_slide =[];
foreach ($Indico->data[$status_key] as $x) {
    $status_slide[$x['code']] =empty($x['editable']) ? false : $x['editable']['state'];
}


$conf_day =$_GET['day'] ?? false;


if (!empty($_GET['ok'])) {
    $pcode =$_GET['ok'];
    
    $status[$pcode] =[
        'ts' =>time(),
        'allow_publication' =>false,
        'author' =>$user['email']
        ];
    
    $Indico->save_file( 'status', 'out_status', 'STATUS', [ 'save_empty' =>true ]);
    header( "location: $_SERVER[PHP_SELF]?day=$conf_day" );
    return;

} else if (!empty($_GET["allow_publication"])) {
    $pcode =$_GET['code'];

    $status[$pcode]['allow_publication'] =$_GET["allow_publication"];
    
    $Indico->save_file( 'status', 'out_status', 'STATUS', [ 'save_empty' =>true ]);
}

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
        if (empty($s['poster_session']) && !empty($s['papers'])) {
            foreach ($s['papers'] as $pcode =>$p) {
                $day_talks ++;
                if (!empty($status[$pcode])) $day_ok ++;
            }
        }
    }

    $wday =date( 'l', strtotime( $day ));
    
    if ($day_talks) {
        $day_status =ceil( $day_ok *100 /$day_talks );

        if ($day == $conf_day) $day_active =$day_id;

        if ($day_status == 100) $status_bar ="<center><div class='status_bar_completed' title='talks verified' style='width: 100%'>completed</div></center>";
        else $status_bar =$day_ok ? "<center><div class='status_bar' title='talks verified' style='width: $day_status%'>$day_status%</div></center>" : "<div class='status_bar' style='width: 0'>&nbsp;</div>";

        $T->set( "day$day_id", sprintf( "<a href='%s?day=%s'>%s, %s</a>%s", $_SERVER['PHP_SELF'], $day, $day, $wday, $status_bar ));

        $day_id ++;
    }

}

if ($day_id <= 5) {
    for (; $day_id <=5; $day_id ++) {
        $T->set( "day$day_id", "" );
    }
}

$content =false;

if (empty($conf_day) || empty($Indico->data['programme']['days'][$conf_day])) {
    $T->set( 'content', $content );
    echo $T->get();
    return;
}


$papers_id =[];

$day_ok =0;
$i =1;

$thead =false;
$rows =false;
foreach ($Indico->data['programme']['days'][$conf_day] as $sid =>$s) {
    if (empty($s['poster_session']) && !empty($s['papers'])) {
        foreach ($s['papers'] as $pcode =>$p) {
            $papers_id[$pcode] =$p['id'];

            list( $presenter, $affiliation ) =explode( ' - ', $p['presenter'] );

            $fname =strtr(sprintf( '%02d-%s-%s', $i, $pcode, $presenter ), ' ', '_' );

            $rows[$i] =[
                'Order' =>empty($status[$pcode]) && !empty($status_slide[$pcode]) ? sprintf( "<a href='%s?pid=%d&download=%s'>%02d</a>", $_SERVER['PHP_SELF'], $p['id'], $fname, $i ) : sprintf( '%02d', $i ),
                'Time' =>$p['time_from'],
                'Code' =>$pcode,
                'Room' =>$s['room'],
                'Type' =>$s['type'],
                'Title' =>$p['title'],
                'Presenter' =>$p['presenter'],
                'Publish' =>""
                ];

            $i ++;

            if (!empty($status[$pcode])) $day_ok ++;
        }
    }
}

$day_status =$day_ok ? ceil( count($rows)*100/$day_ok ) : 0;

if (!empty($rows[1])) {
    $thead .="<tr><th>" .strtr( implode( "</th><th>", array_keys( $rows[1] )), '_', ' ' ) ."</th></tr>\n";
    
    $content .="<div style='margin-bottom: 1em;'></div>
    <table id='talks' class='cell-border'>
    <thead>
    $thead
    <thead>
    <tbody>
    ";
}

foreach ($rows as $r) {
    $pcode =$r['Code'];
    $pid =$papers_id[ $pcode ];

    $contribution_url ="https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$pid";
    $paper_url ="https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$pid/editing/slides";
    $ok_url ="$_SERVER[PHP_SELF]?day=$conf_day&ok=$r[Code]";

    if (empty($status[$pcode])) {
        if (!empty($status_slide[$pcode])) {
            $r['Presenter'] =sprintf( "%s <a href='%s' class='tag ok'>OK</a>", $r['Presenter'], $ok_url );
            $cls ='ready';
        
        } else {
            $cls =false;
        }

    } else {
        switch ($status[$pcode]['allow_publication']) {
            case 'yes':
                $r['Publish'] ="<span class='tag publish_yes'>ALLOWED</span>";
                break;

            case 'no':
                $r['Publish'] ="<span class='tag publish_no'>NOT ALLOWED</span>";
                break;

            default:
                $url ="$_SERVER[PHP_SELF]?day=$conf_day&code=$r[Code]&allow_publication=";
                $r['Publish'] =sprintf( "Allow publication <a href='%syes' class='tag publish_yes'>YES</a>  <a href='%sno' class='tag publish_no'>NO</a>", $url, $url );
                break;
            }
            $cls ='ok';
    }

    if (!empty($status_slide[$pcode])) $r['Code'] =sprintf( "<a href='%s' target='_blank'>%s</a>", $paper_url, $pcode );

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