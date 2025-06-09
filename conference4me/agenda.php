<?php

/* bY Stefano.Deiuri@Elettra.Eu

2023.03.20 - 1st version

*/

require( '../libs/jict-1.0.lib.php' );
require( '../config.php' );

$cfg =config('conference4me');

// print_r( $cfg ); return;

$eid =$cfg['indico_event_id'];

$cache =$cfg['export'];

header( 'Content-Type: application/json' );

$in =[
    'event' =>sprintf( "%s/export_event_%d.json", $cfg['tmp_path'], $eid ),
    'timetable' =>sprintf( "%s/export_timetable_%d.json", $cfg['tmp_path'], $eid )
    ];

$dev =!empty($_GET['dev']);

if (!$dev && empty($_GET['refresh']) && file_exists( $cache ) && filemtime( $cache ) > max( filemtime( $in['event']), filemtime( $in['timetable']))) {
    readfile( $cache );
    return;
}

// $event =file_read_json( $in['event'], true );
$timetable =file_read_json( $in['timetable'], true );

$verbose =false;

$authors =[];

/* foreach ($event['results'][0]['sessions'] as $session_id =>$session) {
    foreach ($session['contributions'] as $x) {
        $authors[ $x['friendly_id'] ] =[];

        foreach (['primaryauthors', 'coauthors'] as $author_type) {
            foreach ($x[$author_type] as $a) {
                $authors[ $x['friendly_id'] ][] =[
                    'firstName' =>$a['first_name'],
                    'familyName' =>$a['last_name'],
                    'name' =>$a['first_name'] .' ' .$a['last_name'],
                    'affiliation' =>$a['affiliation'],
                    'type' =>$author_type
                    ];
            }
        }
    }
} */

foreach ($timetable['results'][$eid] as $day_id =>$day) {
    if ($verbose) echo "Day: $day_id\n";
    foreach ($day as $session_id =>$session) {
        if (!empty($session['entries'])) {
            if ($verbose) echo "  Session: $session_id\n\tentries: ";
            foreach ($session['entries'] as $entry_id =>$x) {
                if ($verbose) echo "$entry_id ($x[code]) ";

                // $timetable['results'][$eid][$day_id][$session_id]['entries'][$entry_id]['authors'] =$authors[$x['friendlyId']];
                $timetable['results'][$eid][$day_id][$session_id]['entries'][$entry_id]['title'] =
                    sprintf("%s: ", $x['code'] )
                    .$timetable['results'][$eid][$day_id][$session_id]['entries'][$entry_id]['title'];

/*                 if ($dev) {
                    $timetable['results'][$eid][$day_id][$session_id]['entries'][$entry_id]['description'] ='...';

                    foreach ($x['presenters'] as $presenters_id =>$presenter) {
                        foreach( ['emailHash', 'email', 'displayOrderKey'] as $key) {
                            unset($presenter[$key]);
                        }
                        $timetable['results'][$eid][$day_id][$session_id]['entries'][$entry_id]['presenters'][$presenters_id] =$presenter;
                    }

                    foreach( ['attachment', 'pdf', '_type', '_fossil', 'url'] as $key) {
                        unset($timetable['results'][$eid][$day_id][$session_id]['entries'][$entry_id][$key]);
                    }
                } */
            }
        }
    }
}

$timetable['url'] =sprintf( '%s/%s/agenda.php', $cfg['root_url'], $cfg['app'] );

if ($dev) {
    echo json_encode( $timetable );
    return;
}

file_write_json( $cache, $timetable );

readfile( $cache );

?>
