<?php

/* by Stefano.Deiuri@Elettra.Eu

2024.05.23 - new filter y2g with link to pdf
2024.05.22 - new filter [y] and sort result for filters
2024.05.18 - filters
2024.05.16 - show yellow to green
2023.04.05 - update (auth & template)
2022.09.01 - add presents
2022.08.23 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_papers', false, false );
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
        tr:hover td { border-bottom: 2px solid black; }
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

$filter =$_GET['filter'] ?? false;

$sort ="[0, 'asc']";
if ($filter == 'y') $sort ="[7,'desc']";
else if ($filter == 'y2g') $sort ="[6,'asc'],[7,'desc']";
else if ($filter == 'qa_pending') $sort ="[9,'desc'],[0,'asc']";


$registrants_email =[];
$presents_email =[];
if (!empty($Indico->data['registrants']['registrants'])) {
    foreach ($Indico->data['registrants']['registrants'] as $rid =>$r) {
        $registrants_email[] =$r['email'];
        if (!empty($r['present'])) $presents_email[] =$r['email'];
    }
}

$rows =false;
foreach ($Indico->data['papers'] as $pcode =>$p) {
    if (empty($p['hide'])) {
        if ($p['status_indico'] != 'Accepted' && $p['status_qa'] == 'QA Pending') {
            $Indico->data['papers'][$pcode]['status_qa'] =$p['status_qa'] ='QA Failed';

        } else if ($p['status_indico'] == 'Accepted' && !empty($p['status_history']) && ($p['status_qa'] != 'QA Approved' || $filter != 'y2g')) {
            $lrs =end( $p['status_history'] );
            if ($lrs == '_changes_acceptance') {
                $p['status_indico'] .=' by Author';
                $Indico->data['papers'][$pcode]['status'] =$p['status'] ='y2g';
            }
        }

        $show =true;
        if (!empty($_GET['qa']) && $_GET['qa'] == 'pending' && $p['status_qa'] != 'QA Pending') $show =false;
        else if ($filter == 'y' && $p['status'] != 'y') $show =false;
        else if ($filter == 'y2g' && $p['status'] != 'y2g') $show =false;
        else if ($filter == 'qa_pending' && $p['status_qa'] != 'QA Pending') $show =false;
        else if ($filter == 'pdf_warnings' && empty($Indico->data['pdf_problems'][$pcode])) $show =false;
        else if (!empty($_GET['pcode']) && strtolower($pcode) != strtolower($_GET['pcode'])) $show =false;

        if ($show) {
            $poster_police =$p['poster']  && !empty($Indico->data['posters_status'][$pcode]['status']) 
                ? $Indico->data['posters_status'][$pcode]['status'] 
                : "";
 
            $author_present ='Not present';
            $author_registered ='Not registered';
            if (!empty($p['authors_emails'])) {
                foreach ($p['authors_emails'] as $email) {
                    if (in_array( $email, $presents_email )) $author_present ='OK';
                    if (in_array( $email, $registrants_email )) $author_registered ='OK';
                }
            }
        
            if (!file_exists("../data/papers/$pcode.pdf")) $pdf_status ='NO PDF';
            else $pdf_status =empty($Indico->data['pdf_problems'][$pcode]) 
                ? 'OK'
                : 'PDF Warning<ul><li>' .implode( '</li><li>', $Indico->data['pdf_problems'][$pcode] ) .'</li></ul>'
                    .($p['pdf_ts'] ? sprintf( "<br /><small>%s</small>", date( 'd/m H:i', $p['pdf_ts'] ) ) : false);

            $rows[] =[
                'Abstract_ID' =>$p['abstract_id'],
                'Program_Code' =>$pcode,
                'Type' =>$p['type'],
                'Title' =>$p['title'],
                'PAuthor' =>sprintf( "%s (%s)", $p['author'], $p['author_inst'] ),
                'Source' =>$p['source_type'],
                'Editor' =>$p['editor'],
                'Status' =>$p['status_indico'],
                'QA' =>$p['status_qa'],
                'PDF' =>$pdf_status,
                'Poster_Police' =>$poster_police,
                'Authors_Check' =>!empty($Indico->data['authors_check'][$pcode]['done']) ? 'OK' : "",
                'Author_Registered' =>$author_registered,
                'Author_Present' =>$author_present,
                'CAT_publish' =>$p['custom_fields']['CAT_publish'] ?? false,
                ];
        }
    }
}

$filters =[
    'All' =>false,
    'QA Pending' =>'?filter=qa_pending',
    'Needs Confirmation' =>'?filter=y',
    'Accepted by Author' =>'?filter=y2g',
    'PDF warnings' =>'?filter=pdf_warnings'
    ];

if (!empty($user['admin'])) $filters['pcode'] ='?pcode=x';

$content =false;

foreach ($filters as $label =>$x) {
    $content .=($content ? ' | ' : false) .sprintf( "<a href='index.php%s'>%s</a>", $x, $label );
}

if (empty($rows)) {
    $T->set( 'content', $content );
    echo $T->get();
    return;
}


$thead ="<tr><th>" .strtr( implode( "</th><th>", array_keys( $rows[0] ) ), '_', ' ' ) ."</th></tr>\n";

$content .="<table id='papers' class='cell-border'>
<thead>
$thead
<thead>
<tbody>
";

$is_admin =!empty($user['admin']);

foreach ($rows as $r) {
    $pcode =$r['Program_Code'];
    $p =$Indico->data['papers'][$pcode];

    $ac_class =$r['Authors_Check'] == 'OK' ? 'b_g' : false;
    $ar_class =$r['Author_Registered'] == 'OK' ? 'b_g' : 'b_r';
    $ap_class =$r['Author_Present'] == 'OK' ? 'b_g' : 'b_r';
    
    $pdf_class =$r['PDF'] == 'OK' ? 'b_g' : 'b_r';
    
    if ($p['status_qa'] == 'QA Approved') $qa_class ='b_g';
    else if ($p['status_qa'] == 'QA Failed') $qa_class ='b_r';
    else $qa_class =false; 
    
    if ($r['Poster_Police'] == 'OK') $pp_class ='b_g';
    else if ($r['Poster_Police'] == 'Fail') $pp_class ='b_r';
    else if ($r['Poster_Police'] == 'Unmanned') $pp_class ='b_y';
    else $pp_class =false; 

    $cat_class =empty($r['CAT_publish']) ? false : 'b_y';
    
    $contribution_url ="https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$p[id]";
    $paper_url ="https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$p[id]/editing/paper";

    $revisions =$is_admin ? sprintf( "<br /><small><a href='./revisions.php?pid=%d' target='_blank'>revisions</a></small>", $p['id'] ) : false;

    $pre_status =false;
    $post_status =false;
    if ($filter == 'y' && $p['status'] == 'y') $pre_status =sprintf( "<small>updated %s days ago</small><br /><br />", round((time() -$p['status_ts'])/86400,0 ));
    else if ($filter == 'y2g' && $p['status'] == 'y2g') {
        $pre_status =sprintf( "<small>updated %s days ago</small><br /><br />", round((time() -$p['status_ts'])/86400,0 ));
        $r['PDF'] =sprintf( "<a href='%s' target='_blank' style='text-decoration: underline;'>PDF</a>", $p['pdf_url'] );
    }

    if ($r['PDF'] != 'OK' && $r['PDF'] != 'NO PDF') $post_status =sprintf( "<br /><br /><small>%s</small>", date( 'd/m H:i', $p['status_ts'] ));

    $content .="<tr>
    <td><a href='$contribution_url' target='_blank'>$r[Abstract_ID]</a></td>
<td><a href='$paper_url' target='_blank'>$pcode</a>$revisions</td>
<td>$r[Type]</td>
<td>$r[Title]</td>
<td>$r[PAuthor]</td>
<td>$r[Source]</td>
<td>$r[Editor]</td>
<td class='b_$p[status]'>$pre_status $r[Status] $post_status</td>
<td class='$qa_class'>$r[QA]</td>
<td class='$pdf_class'>$r[PDF]</td>
<td class='$pp_class'>$r[Poster_Police]</td>
<td class='$ac_class'>$r[Authors_Check]</td>
<td class='$ar_class'>$r[Author_Registered]</td>
<td class='$ap_class'>$r[Author_Present]</td>
<td class='$cat_class'>$r[CAT_publish]</td>
</tr>
";
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

?>