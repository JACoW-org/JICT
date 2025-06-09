<?php

/* by Stefano.Deiuri@Elettra.Eu

2025.05.28 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );


$cfg =config( 'page_team', false, false );
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

$colors =$cfg['colors'];
$sliceColors =[ $colors['g'], $colors['r'], $colors['y'], $colors['files'], $colors['a'], $colors['removed'] ];


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

$content ="<div class='row p-5'>"
    ."<div class='col-md-6'>\n<h2>Editors</h2>\n$editor_content</div>\n"
    ."<div class='col-md-6'>
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