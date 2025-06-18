<?php

/* by Stefano.Deiuri@Elettra.Eu

2025.05.31 - add filters
2025.05.28 - move team statistics to a new page
2024.05.23 - days stats & slides charts
2024.05.07 - update
2023.06.21 - editors QA fail
2023.04.16 - editors stats
2023.03.31 - update (auth & new style)
2023.03.28 - update
2022.08.22 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );


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
        main { font-size: 22px; } 
        .jqstooltip { box-sizing: content-box; }
        ',
    'title' =>$cfg['name'],
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
    'scripts' =>"<script src='../dist/jquery.sparkline.min.js'></script>\n"
    ]);

$vars =[
    'type' =>[ 'label' =>"Types"  ],
    'available' =>[ 'label' =>"Files Available", 'init' =>"Yes,No"  ],
    'source_type' =>[ 'label' =>"File Types" ],
    'source_type_rfr' =>[ 'label' =>"File Types RfR" ],
    'status_indico_1st' =>[ 'label' =>"Paper 1st Status", 'init' =>"Accepted,Needs Changes,Needs Confirmation"  ],
    'status_indico' =>[ 'label' =>"Paper Status", 'init' =>"Accepted,Needs Changes,Needs Confirmation,Ready for Review,Assigned to an Editor,Accepted by Submitter"  ],
    'status_qa' =>[ 'label' =>"Papers QA", 'init' =>'QA Approved,QA Failed,QA Pending'  ],
    'authors_check' =>[ 'label' =>"Authors Check", 'init' =>"Done,Not Ready,Pending,,Assigned"  ],
    'poster_police' =>[ 'label' =>"Posters Check", 'init' =>"OK,Fail,Pending" ],

    'slides_check' =>[ 'label' =>"Slides Check", 'init' =>"Done,Not Ready,Pending"  ],
    'slides_status' =>[ 'label' =>"Slides Editing Status", 'init' =>"Accepted,Needs Submitter Changes,Needs Submitter Confirmation,Ready For Review,Assigned to an Editor"  ],
    'slides_qa' =>[ 'label' =>"Slides QA", 'init' =>'QA Approved,QA Failed,QA Pending' ],
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

$types =[];

foreach ($Indico->data['papers'] as $pcode =>$p) {
    if (empty($types[$p['type']])) $types[$p['type']] =true;

    if (!empty($_GET['filter_by_type']) && $p['type'] != $_GET['filter_by_type']) $p['hide'] =true;
    if (!empty($_GET['filter_by_code']) && strpos( $p['code'], strtoupper($_GET['filter_by_code'])) !== 0) $p['hide'] =true;

    if (empty($p['hide'])) {     
        if ($p['poster'] && !empty($Indico->data['posters_status'][$pcode])) $p['poster_police'] =$Indico->data['posters_status'][$pcode]['status'];

        $p['available'] =empty($p['source_type']) ? 'No' : 'Yes';

        if (($p[$cws_config['page_authors_check']['filter']['key']] == $cws_config['page_authors_check']['filter']['value'])) {
            if (empty($Indico->data['authors_check'][$pcode]['done'])) {
                if (!empty($Indico->data['authors_check'][$pcode]['assigned_to'])) $p['authors_check'] ='Assigned';
                else $p['authors_check'] ='Pending';

            } else {
                $p['authors_check'] ='Done';
            }
        }
    
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

$serie['poster_police']['Pending'] =$serie['type']['Poster Presentation'] -$serie['poster_police']['OK'] -$serie['poster_police']['Fail'];
$serie['authors_check']['Not Ready'] =$serie['available']['Yes'] -$serie['authors_check']['Done'] -$serie['authors_check']['Pending'] -$serie['authors_check']['Assigned'];


// if (me()) print_r( $serie );


if (!empty($Indico->data['editing_tags'])) {
    $tags =false;

    arsort( $Indico->data['editing_tags'] );
    foreach ($Indico->data['editing_tags'] as $label =>$value) {
        if (substr( $label, 0, 3 ) != 'PRC' && substr( $label, 0, 2 ) != 'QA') {
            $percent =$papers_processed ? $value*100/$papers_processed : 0;
            $percent =round( $percent, ($percent < 5 ? 1 : 0) );
            $tags .="<tr><th>$label</th><td>$value</td><td>$percent%</td></tr>\n";
        }
	}

} else {
	$tags ="<i>No data available!</i>";
}




if (empty($_GET['filter_by_type']) && empty($_GET['filter_by_code'])) {
    if (!empty($Indico->data['team']['editors'])) {
        foreach ($Indico->data['team']['editors'] as $x) {
            $serie['status_indico_1st']['Accepted'] +=$x['stats']['g'];
            $serie['status_indico_1st']['Needs Confirmation'] +=$x['stats']['y'];
            $serie['status_indico_1st']['Needs Changes'] +=$x['stats']['r'];
        }
    }
    
    // $serie['slides_check'] =$Indico->data['team']['stats']['slides']['check'];

    foreach ($Indico->data['team']['stats']['slides']['check'] as $key =>$value) {
        $serie['slides_check'][$key] =$value;
    }

    foreach ($Indico->data['team']['stats']['slides']['qa_status'] as $key =>$value) {
        $serie['slides_qa'][$key] =$value;
    }

    foreach ($Indico->data['team']['stats']['slides']['editing_status'] as $key =>$value) {
        $serie['slides_status'][$key] =$value;
    }

} else {
    $tags =false;
}


if (!empty($serie['source_type_rfr'])) arsort($serie['source_type_rfr']);

if (!empty($_GET['export_data'])) {
    $export =$serie;
    
    unset( $export['status_qa'] );
    unset( $export['authors_check'] );
    unset( $export['slides_check'] );
    unset( $export['slides_qa'] );

    $fname =str_replace( "'", "", $cfg['conf_name'] );

    file_write_json( strtolower( sprintf( '../data/%s-proceedings-statistics.json', $fname )), $export ); 
    
    if ($_GET['export_data'] =='json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( $export, true );
        
    } else {
        echo sprintf( "<pre>%s</pre>", htmlspecialchars(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )));
    }

    exit;
}






$colors =$cfg['colors'];
$sliceColors =[ $colors['g'], $colors['r'], $colors['y'], $colors['files'], $colors['a'], $colors['y2g'], $colors['removed'] ];

$last_nums =end($Indico->data['stats']);
$papers_processed =$last_nums['processed'];

$content ="<div class='filter_bar'><a href='$_SERVER[PHP_SELF]'>All</a> ";
foreach ($types as $name =>$t) {
    if (!empty($name)) $content .=sprintf( "| <a href='$_SERVER[PHP_SELF]?filter_by_type=%s'>%s</a> ", urlencode($name), $name );
}
$content .="</div>";


$content .="<div class='row p-5'>";
foreach ($vars as $var =>$vcfg) {
    if (!empty($serie[$var])) $content .=__h( 'div', chart_pie( $var, $serie[$var], [ 'title' =>$vcfg['label'], 'show%' =>true, 'sliceColors' =>json_encode( $sliceColors ) ]), [ 'class' =>'col-md-3 ' ]);
    //else $content .=__h( 'div', " ", [ 'class' =>'col-md-3 ' ]);
}
$content .="</div>\n";

if ($tags) $content .="<div class='row p-5'>"
    ."<div class='col-md-12'>\n<h2>Tags</h2>\n<table class='values'>\n$tags\n</table>\n</div>\n"
    ."</div>\n";    


// $debug =empty($_GET['y75_debug']) ? false : sprintf( "<pre>\n%s</pre>", print_r($Indico->data['editors'],true));

$T->set( 'content', $content .$debug );
$T->set( 'js', $js );

echo $T->get();





//-----------------------------------------------------------------------------
function chart_pie( $_name, $_serie, $_cfg ) {
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

    $tab =false;
    $i =0;
    foreach ($_serie as $label =>$value) {
        if (!empty($label)) {
            $dot =$colors ? "<i class='fa-solid fa-circle' style='color:" .$colors[$i] ."'></i> " : false;
    
            $tab .="<tr><th>$dot$label</th><td>$value</td>" 
                .(empty($_cfg['show%']) ? false : "<td>" .round($value*100/$tot) ."%</td>" )  
                ."</tr>\n";
        }
        
        $i ++;
    }

    $values =implode( ",", array_values( $_serie ));
    
    $labels ="'" .implode( "','", array_keys( $_serie )) ."'";

    $options =false;
    if (!empty($_cfg['sliceColors'])) $options .=", sliceColors: " .$_cfg['sliceColors'];
    
    $html ="
<table class='layout'><tr><td valign='top' style='padding-top:20px;'>
    <span class='sparklines' id='$_name' sparkType='pie' sparkWidth='150px' sparkHeight='150px' values='$values'></span>
    </td>
<td valign='top'>
    <b>$_cfg[title]</b>
    <table class='values'>
    $tab
    </table>
</td></tr>
</table>
";

    $js .="\$('#$_name').sparkline( 'html', { enableTagOptions: true $options, offset: -90 });\n";

    return $html;
}

?>