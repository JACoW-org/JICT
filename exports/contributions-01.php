<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.08.30 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );

$event =file_read_json( '../tmp/indico/export_event_' .$cws_config['global']['indico_event_id'] .'.json', true );
$persons_db =file_read_json( '../data/persons.json', true );


$fname =time().'.csv';
$fp =fopen( $fname, 'w' );

$out =false;

$l =0;
foreach ($event['results'][0]['sessions'] as $session) {
    if (!empty($session['contributions'])) {
        foreach ($session['contributions'] as $c) {
//            print_r( $c ); return;

            $persons =[ "", "", "" ];
            foreach ($c['speakers'] as $p) {
                $al =$persons_db[$p['person_id']]['affiliation_link'];
                $adr =false;
                foreach ([$al['street'], $al['postcode'], $al['city']] as $x) {
                    if (!empty($x)) $adr[] =$x;
                }

                $persons[0] .=($persons[0] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'];
                $persons[1] .=($persons[1] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'] .' (' .$p['affiliation'] .')';
                $persons[2] .=($persons[2] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'] .' <' .$p['email'] .'>';
                $persons[3] .=($persons[3] ? ', ' : false) .$p['affiliation'];
                $persons[4] .=($persons[4] ? ', ' : false) .$al['country_name'];
                $persons[5] .=($persons[5] ? ', ' : false) .implode( ', ', $adr );
            }
            $presenters =$persons;
            
            $persons =[ "", "", "" ];
            foreach ($c['primaryauthors'] as $p) {
                $al =$persons_db[$p['person_id']]['affiliation_link'];
                $adr =false;
                foreach ([$al['street'], $al['postcode'], $al['city']] as $x) {
                    if (!empty($x)) $adr[] =$x;
                }

                $persons[0] .=($persons[0] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'];
                $persons[1] .=($persons[1] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'] .' (' .$p['affiliation'] .')';
                $persons[2] .=($persons[2] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'] .' <' .$p['email'] .'>';
                $persons[3] .=($persons[3] ? ', ' : false) .$p['affiliation'];
                $persons[4] .=($persons[4] ? ', ' : false) .$al['country_name'];
                $persons[5] .=($persons[5] ? ', ' : false) .implode( ', ', $adr );
            }
            $authors =$persons;
            
            $persons =[ "", "", "" ];
            foreach ($c['coauthors'] as $p) {
                $al =$persons_db[$p['person_id']]['affiliation_link'];
                $adr =false;
                foreach ([$al['street'], $al['postcode'], $al['city']] as $x) {
                    if (!empty($x)) $adr[] =$x;
                }

                $persons[0] .=($persons[0] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'];
                $persons[1] .=($persons[1] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'] .' (' .$p['affiliation'] .')';
                $persons[2] .=($persons[2] ? ', ' : false) .$p['first_name'] .' ' .$p['last_name'] .' <' .$p['email'] .'>';
                $persons[3] .=($persons[3] ? ', ' : false) .$p['affiliation'];
                $persons[4] .=($persons[4] ? ', ' : false) .$al['country_name'];
                $persons[5] .=($persons[5] ? ', ' : false) .implode( ', ', $adr );
            }
            $coauthors =$persons;

            $x =array(
                'Id' =>$c['id'],
                'Title' =>$c['title'],
                'Description' =>$c['description'],
                'Date' =>$c['startDate']['date'] .', ' .$c['startDate']['time'],
                'Duration' =>$c['duration'],
                'Type' =>$c['type'],
                'Track' =>$c['track'],
                'Session' =>$c['session'],
                'Presenters' =>$presenters[0],
                'Presenters (affiliation name)' =>$presenters[3],
                'Presenters (affiliation country)' =>$presenters[4],
                'Presenters (affiliation address)' =>$presenters[5],
                'Presenters (affiliation)' =>$presenters[1],
                'Presenters (email)' =>$presenters[2],
                'Materials' =>"",
                'Program Code' =>$c['code'],
                'Authors' =>$authors[0],
                'Authors (affiliation)' =>$authors[1],
                'Co-Authors' =>$coauthors[0],
                'Co-Authors (affiliation)' =>$coauthors[1]
                );

            if ($l == 0) {
                fputcsv( $fp, array_keys($x) );
            }

            fputcsv( $fp, $x );
            $out .="---[ $l ]--------------------------------------------------\n" .substr( print_r( $x, true ), 8, -2 );

            $l ++;
        }
    } 
}

fclose( $fp );

if ($_GET['mode'] == 'show') echo "<pre>$out</pre>";
else {
    download_file( $fname, $cws_config['global']['conf_name'] .'-contributions.csv', 'text/csv' );
}

unlink( $fname );

?>
