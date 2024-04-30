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
    if ($a['state'] != 'withdrawn' && !empty($a['reviews'])) {
        foreach ($a['reviews'] as $r) {
            if (in_array( $r['proposed_action'], [ 'accept', 'change_tracks' ] )) {
                $reviewer_name =sprintf( "%s %s", $r['user']['first_name'], $r['user']['last_name'] );
                $reviewer_id =md5( $reviewer_name . 'YbemPN6sjNd3bTM3kD35mfT3' );

                $reviewers[$reviewer_id] =[ 'name' =>$reviewer_name, 'email' =>$r['user']['email'] ];
                //sprintf( "%s (<a href='mailto:%s'>%s</a>)", $reviewer_name, $r['user']['email'], $r['user']['email'] );
/* 

                if (empty($reviewers[$reviewer_name]['_SUM'])) $reviewers[$reviewer_name]['_SUM'] =[ 'primary' =>0, 'secondary' =>0 ];

                if (empty($reviewers[$reviewer_name][$track_group])) $reviewers[$reviewer_name][$track_group] =[ 'primary' =>0, 'secondary' =>0 ];
    
                foreach ($r['ratings'] as $rr) {
                    if ($rr['question'] == 21 && !empty($rr['value'])) $reviewers[$reviewer_name]['_SUM']['primary'] ++;
                    else if ($rr['question'] == 22 && !empty($rr['value'])) $reviewers[$reviewer_name]['_SUM']['secondary'] ++;

                    if ($rr['question'] == 21 && !empty($rr['value'])) $reviewers[$reviewer_name][$track_group]['primary'] ++;
                    else if ($rr['question'] == 22 && !empty($rr['value'])) $reviewers[$reviewer_name][$track_group]['secondary'] ++;
                } */

                foreach ($r['ratings'] as $rr) {
                    if ($rr['question'] == 21 && !empty($rr['value'])) $vote ='primary';
                    else if ($rr['question'] == 22 && !empty($rr['value'])) $vote ='secondary';
                }

                $speaker =false;
                foreach ($a['persons'] as $person) {
                    if (!empty($person['is_speaker'])) $speaker =sprintf( "%s %s", $person['first_name'], $person['last_name'] );
                }

                $abstracts[$reviewer_id][] =[
                    'id' =>$a['id'],
                    'friendly_id' =>$a['friendly_id'],
                    'title' =>$a['title'],
                    'speaker' =>$speaker,
                    'track_group' =>substr( $r['track']['title'], 0, 3 ),
                    'proposed_action' =>$r['proposed_action'],
                    'vote' =>$vote,
                    ];

            }
        }
    }
}

if (!empty($_GET['show_reviewers_ids'])) {
    echo "<ol>";
    foreach ($reviewers as $id =>$x) {
        echo sprintf( "<li>%s (<a href='mailto:%s?subject=%s&body=%s'>%s</a>)", $x['name'], $x['email'], '[IPAC%2723]%20Your%20Reviews%20Report', urlencode('https://www.ipac23.org/reviews/report.php?rid='.$id) , $x['email']);
    }
    return;
}

$rid =$_GET['rid'];

if (empty($abstracts[ $rid ])) {
    echo "reviewer not found!";
    return;
}

$tbody =false;
$row =false;

foreach ($abstracts[$rid] as $row) {
    $row['id'] =sprintf( "<a href='%s/event/%d/abstracts/%d' target='_blank'>#%d</a>", 
        $cws_config['global']['indico_server_url'], 
        $cws_config['global']['indico_event_id'], 
        $row['id'], 
        $row['friendly_id'] );

    unset( $row['friendly_id'] );

    $tbody .="<tr><td>" .implode( "</td>\n<td>", array_values($row) ) ."</td></tr>\n";
}

$title =$cws_config['global']['conf_name'] .' reviewers votes';

$vars =[
    'info' =>sprintf( "<br />Data updated @ %s<br />Reviews of %s", date( 'r', $ts ), $reviewers[$rid]['name'] ),
    'title' =>$title,
    'table_id' =>str_replace( ' ', '_', $title ),
    'thead' =>"<tr><th>" .implode( "</th>\n<th>", array_keys( $row ) ) ."</th></tr>\n",
    'tbody' =>$tbody,
    'order' =>1
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