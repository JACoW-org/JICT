<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.04.05 - update (auth & template)
2022.08.23 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_authors' );
$cfg['verbose'] =0;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

$Indico->load();

$T =new TMPL( $cfg['template'] );
$T->set([
    'style' =>'main { font-size: 16px; margin-bottom: 2em; } main p { padding-top: 1em; font-size: 18px; font-weight: bold; } div.primary { font-weight: bold; }',
    'title' =>$cfg['name'],
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
    'path' =>'../',
    'head' =>"<link rel='stylesheet' type='text/css' href='../html/datatables.min.css' /><link rel='stylesheet' type='text/css' href='../page_edots/colors.css' />",
    'scripts' =>false,
    '__scripts' =>"<script src='../html/datatables.min.js'></script>",
    '__head' =>"<link rel='stylesheet' type='text/css' href='../page_edots/colors.css' />",
    'js' =>false
    ]);

if ($cfg['post_load_f']) {
    $f =$cfg['post_load_f'];
    $f();
}

$contribution_url ="$cfg[indico_server_url]/event/$cfg[indico_event_id]/contributions";

$show =$_GET['show'];

$rows =false;
$papers =false;

if ($show == 'affiliations') {
    $affiliations =false;
    foreach ($Indico->data['authors'] as $aid =>$a) {
        if (!empty($a['papers'])) {
            $affiliation =trim($a['affiliation']);

            if (empty($affiliations[$affiliation])) {
                $affiliations[$affiliation] =[ 'name' =>$affiliation, 'n_authors' =>1, 'papers' =>[] ];

            } else {
                $affiliations[$affiliation]['n_authors'] ++;
            }

/*             $affiliations[$affiliation]['name'] =$affiliation;
            $affiliations[$affiliation]['n_authors'] =empty($affiliations[$affiliation]['n_authors']) ? 1 : $affiliations[$affiliation]['n_authors'] +1;
 */            

            foreach ($a['papers'] as $pcode =>$p) {
                if ($_GET['filter'] == 'green_only' && $p['status'] != 'g') {
                    // skip

                } else {
                    $affiliations[$affiliation]['papers'][$pcode] =$p;
                }
            }

            ksort( $affiliations[$affiliation]['papers'] );
        }
    }

    foreach ($affiliations as $affiliation =>$a) {
        $papers =false;
        $papers_as_primary =0;
        foreach ($a['papers'] as $pcode =>$p) {
            if (!empty($Indico->data['authors_check'][$pcode]['done'])) $p['status'] ='final';
            $papers .="<div class='paper_code b_$p[status]" .($p['primary'] ? ' primary' : false) ."' pid='$p[id]'>$pcode</div> ";
            if ($p['primary']) $papers_as_primary ++;
        }

        $rows[] =[
            'Affiliation' =>$affiliation,
            'N_Authors' =>$a['n_authors'],
            'N_Papers_Primary' =>$papers_as_primary,
            'N_Papers' =>count($a['papers']),
            'Papers' =>$papers
            ];
    }


} else {
    foreach ($Indico->data['authors'] as $aid =>$a) {
        $papers_as_primary =0;

        if (!empty($a['papers'])) {
            $papers =false;
            foreach ($a['papers'] as $pcode =>$p) {
                if (!empty($Indico->data['authors_check'][$pcode]['done'])) $p['status'] ='final';

                $papers .="<div class='paper_code b_$p[status]" .($p['primary'] ? ' primary' : false) ."' pid='$p[id]'>$pcode</div>";

                if ($p['primary']) $papers_as_primary ++;
            }

            $rows[] =[
                'Name' =>$a['name'],
                'Affiliation' =>$a['affiliation'],
                'N_Papers_Primary' =>$papers_as_primary,
                'N_Papers' =>count($a['papers']),
                'Papers' =>$papers
                ];
        }
    }
}

$thead .="<tr><th>" .str_replace( '_', '&nbsp;', implode( "</th><th>", array_keys(reset($rows)))) ."</th></tr>\n";

$content ="
<p>Switch to " .($show == 'affiliations' ?  "<a href='index.php'>Authors</a>" : "<a href='index.php?show=affiliations'>Affiliations</a>") ."</h1>
<table id='authors' class='cell-border " .($show == 'affiliations' ? ' affiliations_view' : false) ."'>
<thead>
$thead
<thead>
<tbody>
";

$lname =false;
foreach ($rows as $r) {
    if ($show == 'affiliations') {
        $content .="<tr>
            <td>$r[Affiliation]</td>
            <td>$r[N_Authors]</td>
            <td>$r[N_Papers_Primary]</td>
            <td>$r[N_Papers]</td>
            <td class='affiliations_view'>$r[Papers]</td>
            </tr>
            ";
    } else {
        $aff_class =empty($r['Affiliation']) ? 'b_r' : false;
        $name_class =($lname == $r['Name']) ? 'b_r' : false;

        $content .="<tr>
            <td class='$name_class'>$r[Name]</td>
            <td class='$aff_class'>$r[Affiliation]</td>
            <td>$r[N_Papers_Primary]</td>
            <td>$r[N_Papers]</td>
            <td>$r[Papers]</td>
            </tr>
            ";
    }

    $lname =$r['Name'];
}
$content .="</table>";

$T->set( 'js', "
$(document).ready(function() {
    $('#authors').DataTable({
        dom: '<if<t>B>',
        paging: false,
		order: [[0, 'asc']],
        buttons: [
			{ text: 'Export list as CSV', extend: 'csvHtml5', title: 'authors' },
			{ text: 'Export list as XLS (Excel)', extend: 'excelHtml5', title: 'authors' }
			]        
    });
} );

$( 'span.paper_code' ).on( 'click', function() {
    console.dir( $(this) );
    console.log( $(this).text() +' -> ' +$(this).attr( 'pid' ) );

    window.open( '$contribution_url/' +$(this).attr( 'pid' ), 'contribution' );
  });
" );

$T->set( 'content', $content );

echo $T->get();

?>