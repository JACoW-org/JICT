#!/usr/bin/php
<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.08.25 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
$cfg =config();

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-verbose': 
			$cfg['verbose'] =$argv[++$i]; 
			break;

		case '-quiet': 
			$cfg['verbose'] =0;
			break;

		case '-r': 
		case '-refresh': 
			$cfg['cache_time'] =0; 
			break;

		case '-f':
		case '-force':
			$cfg['force'] =true;
			break;

		case '-help':
			echo "options:\n"
				."\t-cleanup: clear cached data\n"
				."\t-verbose n: set verbose level to n\n"
				."\n";
			break;
		}
}


if (!INDICO) return;

require_lib( 'indico', '1.0' );

$Indico =new INDICO( $cfg );
$Indico->load();

$papers =file_read_json( '../data/papers.json', true );

foreach ($Indico->data['authors_check'] as $pcode =>$x) {
    if (!empty($x['done'])) {
        echo "$pcode... ";

        $p =$papers[$pcode];

        $c =$Indico->request( "/event/{id}/contributions/$p[id].json", 'GET', false, array( 'return_data' =>true, 'quiet' =>true ));

        $fail =false;
        foreach ($c['persons'] as $author) {
            if ($author["author_type"] == "none") {
                echo "($author[first_name] $author[last_name]) ";
                $fail =true;
            }
        }

        echo ($fail ? "Error" : "OK") ."\n";
    }
}

?>