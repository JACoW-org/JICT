<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.01.09 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );

$template_fname ="reviewers-01-template.html";

$src ='../tmp/indico/event_41_manage_abstracts_abstracts.json';

$ts =filemtime( $src );
$json =file_read_json( $src, true );

$abstracts =[];
$reviewers =[];

$i =0;
foreach ($json['abstracts'] as $a) {
    
//    if ($a['state'] != 'withdrawn' && !empty($a['reviews']) && in_array( $a['reviews']['proposed_action'], [ 'accept', 'change_tracks' ] )) {
    if ($a['state'] != 'withdrawn' && !empty($a['reviews'])) {
        
        foreach ($a['reviews'] as $r) {
            if (in_array( $r['proposed_action'], [ 'accept', 'change_tracks' ] )) {
                $reviewer_name =sprintf( "%s %s", $r['user']['first_name'], $r['user']['last_name'] );
                $track_group =substr( $r['track']['title'], 0, 3 );          

                if (empty($reviewers[$reviewer_name]['_SUM'])) $reviewers[$reviewer_name]['_SUM'] =[ 'primary' =>0, 'secondary' =>0 ];

                if (empty($reviewers[$reviewer_name][$track_group])) $reviewers[$reviewer_name][$track_group] =[ 'primary' =>0, 'secondary' =>0 ];
    
                foreach ($r['ratings'] as $rr) {
                    if ($rr['question'] == 21 && !empty($rr['value'])) $reviewers[$reviewer_name]['_SUM']['primary'] ++;
                    else if ($rr['question'] == 22 && !empty($rr['value'])) $reviewers[$reviewer_name]['_SUM']['secondary'] ++;

                    if ($rr['question'] == 21 && !empty($rr['value'])) $reviewers[$reviewer_name][$track_group]['primary'] ++;
                    else if ($rr['question'] == 22 && !empty($rr['value'])) $reviewers[$reviewer_name][$track_group]['secondary'] ++;
                }
            }
        }

/*         $abstracts[$i] =[
            'friendly_id' =>$a['friendly_id'],
            'title' =>$a['title'],
            'track_group' =>substr( $a['reviewed_for_tracks']['title'], 0, 3 ),
            'submiter' =>""
            ];
 */
    }
}

ksort($reviewers);

//echo sprintf( "<pre>%s</pre>", print_r( $reviewers ));


$tbody =false;
$row =false;

foreach ($reviewers as $r_name =>$tracks) {
    foreach ($tracks as $t_name =>$votes) {
        foreach ([ 'primary', 'secondary'] as $x) {
            if ($votes[$x] > 40) $votes[$x] =sprintf( "<b style='color:red;'>%s</b>", $votes[$x] );
        }

        $row =[
            'reviewer' =>$r_name,
            'track_group' =>$t_name,
            'vote_primary' =>$votes['primary'],
            'vote_secondary' =>$votes['secondary']
            ];
    
        $tbody .="<tr><td>" .implode( "</td>\n<td>", array_values($row) ) ."</td></tr>\n";
    }
}

$title =$cws_config['global']['conf_name'] .' reviewers votes';

$vars =[
    'info' =>'Data updated @ ' .date( 'r', $ts ),
    'title' =>$title,
    'table_id' =>str_replace( ' ', '_', $title ),
    'thead' =>"<tr><th>" .implode( "</th>\n<th>", array_keys( $row ) ) ."</th></tr>\n",
    'tbody' =>$tbody,
    'order' =>0
    ];

if (!file_exists( $template_fname )) {
    echo "$template_fname template not found!";
    exit;
}

$page =file_read( $template_fname );

foreach ($vars as $var =>$value) {
    $page =str_replace( '{'.$var.'}', $value, $page );
}

echo $page;

?>