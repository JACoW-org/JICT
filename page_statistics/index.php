<?php

/* by Stefano.Deiuri@Elettra.Eu

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

//session_start();

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
    'status_indico' =>[ 'label' =>"Paper Status", 'init' =>"Accepted,Needs Changes,Needs Confirmation,Ready for Review,Assigned to an Editor"  ],
    'status_qa' =>[ 'label' =>"Papers QA", 'init' =>'QA Approved,QA Failed,QA Pending'  ],
    'authors_check' =>[ 'label' =>"Authors Check", 'init' =>"Yes,No"  ],
    'poster_police' =>[ 'label' =>"Posters Check", 'init' =>"OK,Fail" ],

    'slides_check' =>[ 'label' =>"Slides Check", 'init' =>"Yes,No"  ],
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

foreach ($Indico->data['papers'] as $pcode =>$p) {
    if (empty($p['hide'])) {     
        if ($p['poster'] && !empty($Indico->data['posters_status'][$pcode])) $p['poster_police'] =$Indico->data['posters_status'][$pcode]['status'];

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

$serie['slides_check'] =$Indico->data['team']['stats']['slides']['check'];
$serie['slides_qa'] =$Indico->data['team']['stats']['slides']['qa_status'];
foreach ($Indico->data['team']['stats']['slides']['editing_status'] as $key =>$value) {
    $serie['slides_status'][$key] =$value;
}

//if ($_GET['debug']) print_r($serie);

//print_r( $serie ); exit;

if (!empty($serie['source_type_rfr'])) arsort($serie['source_type_rfr']);

$colors =$cfg['colors'];
$sliceColors =[ $colors['g'], $colors['r'], $colors['y'], $colors['files'], $colors['a'], $colors['removed'] ];

$last_nums =end($Indico->data['stats']);
$papers_processed =$last_nums['processed'];

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

$editors =false;
$editor_content =false;
$n =1;
if (!empty($Indico->data['team']['editors'])) {
    foreach ($Indico->data['team']['editors'] as $x) {
        $sum =$x['stats']['g'] +$x['stats']['r'] +$x['stats']['y'];

        $serie['status_indico_1st']['Accepted'] +=$x['stats']['g'];
        $serie['status_indico_1st']['Needs Confirmation'] +=$x['stats']['y'];
        $serie['status_indico_1st']['Needs Changes'] +=$x['stats']['r'];

        $pie_values =$x['stats']['g'] .',' .$x['stats']['r'] .',' .$x['stats']['y'];
        
        $row =[
            'Revisions' =>$x['stats']['revisions'],

            'Started' =>$sum,
            'Running' =>$x['stats']['a'], 
            'Waiting' =>$x['stats']['waiting'], 

            'QA Approved' =>$x['stats']['qa_ok'], 
            'QA Failed' =>$x['stats']['qa_fail'],

            'Green*' =>$x['stats']['g'],
            'Yellow*' =>$x['stats']['y'],
            'Red*' =>$x['stats']['r'],

            '*1<sup>st</sup> status' =>"<span class='sparklines_editor' sparkType='pie' sparkWidth='30px' sparkHeight='30px' values='$pie_values'></span>"
            ];

        $editors .="<tr>\n\t<th>$n - $x[name]</th>\n\t<td>" .implode( "</td>\n\t<td>", array_values( $row )) ."</td>\n\t</tr>\n";

        $n ++;
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



$type ='days';
$table[$type] =false;
if (!empty($Indico->data['team']['stats'][$type])) {
    $headers =[];
    foreach ($Indico->data['team']['stats'][$type]['editors_revisions'] as $date =>$value) {
        $headers[] =substr( $date, 5 );
    }
    $year =substr( $date, 0, 4 );
    sort($headers);
    $headers[] ='Sum';

    $tbody =false;

    foreach ($Indico->data['team']['stats'][$type] as $group =>$stats) {
        $row =[];
        foreach ($headers as $date) {
            if ($date != 'Sum') $row[] =empty($stats["$year-$date"]) ? '-': $stats["$year-$date"];
        }

        $sum =0;
        foreach ($stats as $day =>$value) $sum +=$value;
        $row[] =$sum;

        $label =str_replace(['Qa','Ok'],['Papers QA','OK'],ucwords(strtr( $group, '_', ' ' )));
        $tbody .="<tr>\n\t<th>$label</th>\n\t<td>" .implode( "</td>\n\t<td>", array_values( $row )) ."</td>\n\t</tr>\n";
    }

    $table[$type] =sprintf( "<h2>%s</h2>", ucwords(strtr( $type, '_', ' ')))
        ."<table class='values days'>\n"
        ."<thead>"
            ."<tr><th></th><th>" .implode( "</th><th>", $headers ) ."</th></tr>"
        ."</thead>"
        ."<tbody>"
            .$tbody
        ."</tbody>"
        ."</table>\n";
}



$type ='authors_check';
$table[$type] =false;
if (!empty($Indico->data['team'][$type])) {
    
    uasort($Indico->data['team'][$type]['people'], function ($a, $b) {
        return $a['count'] < $b['count'];
        });

/*     $headers =[];    
    foreach ($Indico->data['team'][$type]['days'] as $day =>$value) {
        $headers[] =$day;
    }
    $headers[] ='Sum'; */
    
    $tbody =false;
    $n =1;
    foreach ($Indico->data['team'][$type]['people'] as $name =>$stats) {
        $row =[];
        $sum =0;
        foreach ($headers as $date) {
            if ($date != 'Sum') {
                $row[] =empty($stats['days'][$date]) ? '-' : $stats['days'][$date];  
                if (!empty($stats['days'][$date])) $sum +=$stats['days'][$date];
            }      
        }
        $row[] =$sum;

        $tbody .="<tr>\n\t<th>$n - $name</th>\n\t<td>" .implode( "</td>\n\t<td>", array_values( $row )) ."</td>\n\t</tr>\n";

        $n ++;
    }

    
	$table[$type] =sprintf( "<h2><br />%s</h2>", ucwords(strtr( $type, '_', ' ')))
        ."<table class='values days'>\n"
        ."<thead>"
            ."<tr><th></th><th>" .implode( "</th><th>", $headers ) ."</th></tr>"
        ."</thead>"
        ."<tbody>"
            .$tbody
        ."</tbody>"
        ."</table>\n";
}




$type ='editors_revisions';
$table[$type] =false;
if (!empty($Indico->data['team']['stats'][$type])) {
    $tbody =false;

    ksort($Indico->data['team']['stats'][$type]);

    $n =1;
    foreach ($Indico->data['team']['stats'][$type] as $label =>$stats) {
        $row =[];
        foreach ($headers as $date) {
            if ($date != 'Sum') $row[] =empty($stats["$year-$date"]) ? '-': $stats["$year-$date"];
        }

        $sum =0;
        foreach ($stats as $day =>$value) $sum +=$value;
        $row[] =$sum;

        $tbody .="<tr>\n\t<th>$n - $label</th>\n\t<td>" .implode( "</td>\n\t<td>", array_values( $row )) ."</td>\n\t</tr>\n";

        $n ++;
    }

    $table[$type] =sprintf( "<h2><br />%s</h2>", ucwords(strtr( $type, '_', ' ')))
        ."<table class='values days'>\n"
        ."<thead>"
            ."<tr><th></th><th>" .implode( "</th><th>", $headers ) ."</th></tr>"
        ."</thead>"
        ."<tbody>"
            .$tbody
        ."</tbody>"
        ."</table>\n";
}



$content ="<div class='row p-5'>";
foreach ($vars as $var =>$vcfg) {
    if (!empty($serie[$var])) $content .=__h( 'div', chart_pie( $serie[$var], array( 'title' =>$vcfg['label'], 'show%' =>true, 'sliceColors' =>json_encode( $sliceColors ) )), [ 'class' =>'col-md-3 ' ]);
    //else $content .=__h( 'div', " ", [ 'class' =>'col-md-3 ' ]);
}
$content .="</div>\n";

$content .="<div class='row p-5'>"
    ."<div class='col-md-4'>\n<h2>Tags</h2>\n<table class='values'>\n$tags\n</table>\n</div>\n"
    ."<div class='col-md-4'>\n<h2>Editors</h2>\n$editor_content</div>\n"
    ."<div class='col-md-4'>
        $table[days]
        $table[authors_check]
        $table[editors_revisions]
        </div>"
    ."</div>\n";    


// $debug =empty($_GET['y75_debug']) ? false : sprintf( "<pre>\n%s</pre>", print_r($Indico->data['editors'],true));

$T->set( 'content', $content .$debug );
$T->set( 'js', $js );

echo $T->get();


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

    $tab =false;
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
    
    $html ="
<table class='layout'><tr><td valign='top' style='padding-top:20px;'>
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

    $js .="\$('.sparklines').sparkline( 'html', { enableTagOptions: true $options, offset: -90 });\n";

    return $html;
}

?>