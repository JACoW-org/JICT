<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.06.21 - editors QA fail
2023.04.16 - editors stats
2023.03.31 - update (auth & new style)
2023.03.28 - update
2022.08.22 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

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
    'style' =>'main { font-size: 22px; } table.editors td { text-align: center !important; } table.editors tbody tr:hover td, table.editors tbody tr:hover th { background: #aaff00; }',
    'title' =>$cfg['name'],
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
//     ." " .__h( 'i', "", [ 'class' =>'fa fa-power-off', 'onClick' =>"document.location =\"$_SERVER[PHP_SELF]?cmd=logout\"" ]),
    'scripts' =>"<script src='../dist/jquery.sparkline.min.js'></script>\n<script src='../dist/chart.min.js'></script>"
    ]);

$vars =[
    'type' =>[ 'label' =>"Types"  ],
    'available' =>[ 'label' =>"Files Available", 'init' =>"Yes,No"  ],
    'source_type' =>[ 'label' =>"File Types" ],
    'source_type_rfr' =>[ 'label' =>"File Types RfR" ],
    'status_indico' =>[ 'label' =>"Paper Status", 'init' =>"Accepted,Needs Changes,Needs Confirmation,Ready for Review,Assigned to an Editor"  ],
    'status_indico_1st' =>[ 'label' =>"Paper 1st Status", 'init' =>"Accepted,Needs Changes,Needs Confirmation"  ],
//    'status' =>[ 'label' =>"Paper Status 2", 'init' =>'g,r,y,files,a' ],
    'status_qa' =>[ 'label' =>"Papers Check (QA)", 'init' =>'QA Approved,QA Failed,QA Pending'  ],
    'authors_check' =>[ 'label' =>"Authors Check", 'init' =>"Yes,No"  ],
    'poster_police' =>[ 'label' =>"Posters Check", 'init' =>"OK,Fail" ]
    ];

$js =false;

$serie =false;

foreach ($vars as $var =>$vcfg) {
    if (!empty($vcfg['init'])) {
        foreach (explode( ',', $vcfg['init']) as $label) {
            $serie[$var][$label] =0;
        }
    }
}

$posters =false;

foreach ($Indico->data['papers'] as $pcode =>$p) {
    if (empty($p['hide'])) {     
        if ($p['poster']) $p['poster_police'] =$Indico->data['posters_status'][$pcode]['status'];

        $p['available'] =empty($p['source_type']) ? 'No' : 'Yes';
        //if (($p['status_qa'] == 'QA Approved')) 
        if (($p[$cws_config['page_authors_check']['filter']['key']] == $cws_config['page_authors_check']['filter']['value'])) $p['authors_check'] =empty($Indico->data['authors_check'][$pcode]['done']) ? 'No' : 'Yes';
    
        if ($p['status_indico'] == 'Ready for Review') {
            if (empty($p['editor'])) $p['source_type_rfr'] =$p['source_type'];
            else $p['status_indico'] ='Assigned to an Editor';
        }

        foreach ($vars as $var =>$vcfg) {
            if (!empty($p[$var])) {
                if (empty($serie[$var][$p[$var]])) $serie[$var][$p[$var]] =1;
                else $serie[$var][$p[$var]] ++;
            }
        }
    }
}

arsort($serie['source_type_rfr']);

$colors =$cfg['colors'];
$sliceColors =[ $colors['g'], $colors['r'], $colors['y'], $colors['files'], $colors['a'], $colors['removed'] ];

$last_nums =end($Indico->data['stats']);
$papers_processed =$last_nums['processed'];

if (!empty($Indico->data['editing_tags'])) {
    $tags =false;

    arsort( $Indico->data['editing_tags'] );
    foreach ($Indico->data['editing_tags'] as $label =>$value) {
        if (substr( $label, 0, 3 ) != 'PRC' && substr( $label, 0, 2 ) != 'QA') {
            $percent =$value*100/$papers_processed;
            $percent =round( $percent, ($percent < 5 ? 1 : 0) );
            $tags .="<tr><th>$label</th><td>$value</td><td>$percent%</td></tr>\n";
        }
	}

} else {
	$tags ="<i>No data available!</i>";
}

$editor_content =false;
if (!empty($Indico->data['editors'])) {
    foreach ($Indico->data['editors'] as $x) {
        $serie['status_indico_1st']['Accepted'] +=$x['stats']['g'];
        $serie['status_indico_1st']['Needs Confirmation'] +=$x['stats']['y'];
        $serie['status_indico_1st']['Needs Changes'] +=$x['stats']['r'];

        $pie_values =$x['stats']['g'] .',' .$x['stats']['r'] .',' .$x['stats']['y'];

        $row =[
            'Revisions' =>$x['stats']['revisions'],
            'Completed' =>$x['complete'], 
            'Under processing' =>$x['stats']['a'], 
            'QA Failed' =>__h( 'span', round($x['stats']['qa_fail']*100/$x['complete'],0) .'%', [ 'title' =>$x['stats']['qa_fail'] ]),
            'Green' =>round($x['stats']['g']*100/$x['complete'],0) .'%',
            'Yellow' =>round($x['stats']['y']*100/$x['complete'],0) .'%',
            'Red' =>round($x['stats']['r']*100/$x['complete'],0) .'%',
            'Chart' =>"<span class='sparklines_editor' sparkType='pie' sparkWidth='30px' sparkHeight='30px' values='$pie_values'></span>"
            ];

        $editors .="<tr>\n\t<th>$x[name]</th>\n\t<td>" .implode( "</td>\n\t<td>", array_values( $row )) ."</td>\n\t</tr>\n";
    }

    $options =false;

	$editor_content ="<table class='values editors'>\n"
	    ."<thead>"
	    ."<tr><th>Name</th><th>" .implode( "</th><th>", array_keys( $row )) ."</th></tr>"
	    ."</thead>"
	    ."<tbody>"
	    .$editors
	    ."</tbody>"
	    ."</table>\n";

    $js .="\$('.sparklines_editor').sparkline( 'html', { enableTagOptions: true, sliceColors: " .json_encode( $sliceColors ) .", offset: -90 });\n";

} else {
	$editor_content ="<i>No data available!</i>";
}


$content ="<div class='row p-5'>";
foreach ($vars as $var =>$vcfg) {
    $content .=__h( 'div', chart_pie( $serie[$var], array( 'title' =>$vcfg['label'], 'show%' =>true, 'sliceColors' =>json_encode( $sliceColors ) )), [ 'class' =>'col-md-3 ' ]);
}
$content .="</div>\n";

$content .="<div class='row p-5'><div class='col-md-6'>\n<h2>Tags</h2>\n<table class='values'>\n$tags\n</table>\n</div>\n";

$content .="<div class='col-md-1'></div><div class='col-md-5'>\n<h2>Editors</h2>\n$editor_content</div>\n"
    ."</div>\n";


$T->set( 'content', $content );
$T->set( 'js', $js );

echo $T->get();

/* 
$page =file_read( $cfg['template'] );
			
foreach (array( 'page_title' =>APP_NAME, 'content' =>$content ) as $var =>$value) {
    $page =str_replace( '{'.$var.'}', $value, $page );
}

echo $page; */

//print_r( $cfg );


//-----------------------------------------------------------------------------
function chart_pie( $_serie, $_cfg ) {
    global $js;

    $tot =0;
    foreach ($_serie as $label =>$value) {
        $tot +=$value;
    }

    if ($tot == 0) return;

    if (!empty($_cfg['sliceColors'])) {
        $colors =json_decode( $_cfg['sliceColors'], true );
        reset($colors);
    } else {
        $colors =false;
    }

    $i =0;
    foreach ($_serie as $label =>$value) {
        $dot =$colors ? "<i class='fa-solid fa-circle' style='color:" .$colors[$i] ."'></i> " : false;

        $tab .="<tr><th>$dot$label</th><td>$value</td>" 
            .(empty($_cfg['show%']) ? false : "<td>" .round($value*100/$tot) ."%</td>" )  
            ."</tr>\n";

        $i ++;
    }

    $values =implode( ",", array_values( $_serie ));
    
    $labels ="'" .implode( "','", array_keys( $_serie )) ."'";

    $options =false;
    if (!empty($_cfg['sliceColors'])) $options .=", sliceColors: " .$_cfg['sliceColors'];
    
//    <div class='var'>
    $html ="
<table class='layout'><tr><td>
    <span class='sparklines' sparkType='pie' sparkWidth='150px' sparkHeight='150px' values='$values'></span>
    </td>
<td valign='top'>
    <b>$_cfg[title]</b>
    <table class='values'>
    $tab
    </table>
</td></tr>
</table>
";
//</div>

/* 
<script type='text/javascript'>
\$('.sparklines').sparkline( 'html', { enableTagOptions: true $options, offset: -90 });
</script>
*/

    $js .="\$('.sparklines').sparkline( 'html', { enableTagOptions: true $options, offset: -90 });\n";

    return $html;
}

?>
