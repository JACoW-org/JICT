<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.01.13 - hightlight author without country
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
$change_track =0;
foreach ($json['abstracts'] as $a) {
    if ($a['state'] != 'withdrawn') {
        $votes =[ 'primary' =>0, 'secondary' =>0 ];

        $tracks =false;
        if (!empty($a['reviews'])) {
            foreach ($a['reviews'] as $r) {
                if ($r['proposed_action'] == 'change_tracks') $change_track ++;

                if (in_array( $r['proposed_action'], [ 'accept', 'change_tracks' ] )) {
                    foreach ($r['ratings'] as $rr) {
                        if ($rr['question'] == 21 && !empty($rr['value'])) $votes['primary'] ++;
                        else if ($rr['question'] == 22 && !empty($rr['value'])) $votes['secondary'] ++;
                    }  

                    if (empty($r['proposed_tracks'])) {
                        $tracks[ $r['track']['title'] ] =true;
                        
                    } else {
                        $tracks[ $r['proposed_tracks'][0]['title'] ] =true;
                        $tracks[ $a['submitted_for_tracks'][0]['title'] ] =true;
                    }
                }
            }
        } 

        if (empty($tracks)) $tracks[ $a['submitted_for_tracks'][0]['title'] ] =true;

        $speakers =false;
        $speakers_country =false;
        foreach ($a['persons'] as $person) {
            if (!empty($person['is_speaker'])) {
                $warn =empty($person['affiliation_link']['country_name']);

                if ($warn) {
                    $pre ="<span style='background: #ff7979; padding: 3px;'>";
                    $post ="</span>";
                } else {
                    $pre =$post =false;
                }

                $speakers_country .=($speakers_country ? ';<br />' : false) .$pre .($warn ? '??' : sprintf( "%s", $person['affiliation_link']['country_name'] )) .$post;
                $speakers .=($speakers ? ';<br />' : false) .$pre .sprintf( "%s %s (%s)", $person['first_name'], $person['last_name'], $person['affiliation'] ) .$post;
            }
        }

        foreach ($tracks as $track =>$t) {
            $abstracts[] =[
                'id' =>$a['id'],
                'friendly_id' =>$a['friendly_id'],
                'title' =>$a['title'],
                'speakers' =>$speakers,
                'speakers<br />country' =>$speakers_country,
                'mc' =>substr( $track, 0, 3 ),
                'track' =>$track,
                'vote<br />primary' =>$votes['primary'],
                'vote<br />secondary' =>$votes['secondary']
                ];
        }
    }
}

$tbody =false;
$row =false;

foreach ($abstracts as $row) {
    $row['id'] =sprintf( "<a href='%s/event/%d/abstracts/%d' target='_blank'>#%d</a>", 
        $cws_config['global']['indico_server_url'], 
        $cws_config['global']['indico_event_id'], 
        $row['id'], 
        $row['friendly_id'] );

    unset( $row['friendly_id'] );

    $tbody .="<tr><td>" .implode( "</td>\n<td>", array_values($row) ) ."</td></tr>\n";
}

$title =$cws_config['global']['conf_name'] .' abstracts votes';

$vars =[
    'info' =>sprintf( "<br />Data updated @ %s", date( 'r', $ts ) ),
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