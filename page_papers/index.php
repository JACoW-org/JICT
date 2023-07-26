<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.04.05 - update (auth & template)
2022.09.01 - add presents
2022.08.23 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_papers', false, false );
$cfg['verbose'] =0;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

$Indico->load();

$T =new TMPL( $cfg['template'] );
$T->set([
    'style' =>'main { font-size: 14px; margin-bottom: 2em } td.b_x { background: #555; color: white }',
    'title' =>$cfg['name'],
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
    'path' =>'../',
    'head' =>"<link rel='stylesheet' type='text/css' href='../html/datatables.min.css' />
    <link rel='stylesheet' type='text/css' href='../page_edots/colors.css' />
    <link rel='stylesheet' type='text/css' href='style.css' />",
    'scripts' =>"<script src='../html/datatables.min.js'></script>",
    'js' =>false
    ]);

if ($cfg['post_load_f']) {
    $f =$cfg['post_load_f'];
    $f();
}

/* $posters =false; */

$registrants_email =false;
$presents_email =false;
foreach ($Indico->data['registrants']['registrants'] as $rid =>$r) {
    $registrants_email[] =$r['email'];
    if (!empty($r['present'])) $presents_email[] =$r['email'];
}

$rows =false;
foreach ($Indico->data['papers'] as $pcode =>$p) {
    if (empty($p['hide'])) {

        $poster_police =$p['poster'] ? $Indico->data['posters_status'][$pcode]['status'] : "";
 
        $author_present ='Not present';
        $author_registered ='Not registered';
        foreach ($p['authors_emails'] as $email) {
            if (in_array( $email, $presents_email )) $author_present ='OK';
            if (in_array( $email, $registrants_email )) $author_registered ='OK';
        }
   
        if (empty($p['pdf_url'])) $pdf_status ='NO PDF';
        else $pdf_status =empty($Indico->data['pdf_problems'][$pcode]) ? 'OK' : 'PDF Warning<ul><li>' .implode( '</li><li>', $Indico->data['pdf_problems'][$pcode] ) .'</li></ul>';

        $rows[] =array(
            'Abstract_ID' =>$p['abstract_id'],
            'Program_Code' =>$pcode,
            'Type' =>$p['type'],
            'Title' =>$p['title'],
            'Editor' =>$p['editor'],
            'Status' =>$p['status_indico'],
            'QA' =>$p['status_qa'],
            'PDF' =>$pdf_status,
            'Poster_Police' =>$poster_police,
            'Authors_Check' =>!empty($Indico->data['authors_check'][$pcode]['done']) ? 'OK' : "",
            'Author_Registered' =>$author_registered,
            'Author_Present' =>$author_present,
            );
    }
}

$thead .="<tr><th>" .strtr( implode( "</th><th>", array_keys( $rows[0] ) ), '_', ' ' ) ."</th></tr>\n";

$content ="<table id='papers' class='cell-border'>
<thead>
$thead
<thead>
<tbody>
";

foreach ($rows as $r) {
    $pcode =$r['Program_Code'];
    $p =$Indico->data['papers'][$pcode];
    
//    $qa_class =$p['status_qa'] == 'QA Approved' ? 'b_g' : false;

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
    
    $contribution_url ="https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$p[id]";
    $paper_url ="https://indico.jacow.org/event/$cfg[indico_event_id]/contributions/$p[id]/editing/paper";

    $content .="<tr>
    <td><a href='$contribution_url' target='_blank'>$r[Abstract_ID]</a></td>
<td><a href='$paper_url' target='_blank'>$pcode</a></td>
<td>$r[Type]</td>
<td>$r[Title]</td>
<td>$r[Editor]</td>
<td class='b_$p[status]'>$r[Status]</td>
<td class='$qa_class'>$r[QA]</td>
<td class='$pdf_class'>$r[PDF]</td>
<td class='$pp_class'>$r[Poster_Police]</td>
<td class='$ac_class'>$r[Authors_Check]</td>
<td class='$ar_class'>$r[Author_Registered]</td>
<td class='$ap_class'>$r[Author_Present]</td>
</tr>
";
}
$content .="</table>";

$T->set( 'js', "
$(document).ready(function() {
    $('#papers').DataTable({        
        dom: \"<'row'<'col-sm-6'i><'col-sm-6'f>><'row'<'col-sm-12'tr>>\",
        paging: false,
		order: [[0, 'asc']]
    });
} );
" );

$T->set( 'content', $content );

echo $T->get();

?>