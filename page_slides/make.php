#!/usr/bin/php
<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.09.15 - fix check fonts
2022.08.30 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_slides' );

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-v': 
		case '-verbose': 
			$cfg['verbose'] =$argv[++$i]; 
			break;

		case '-q': 
		case '-quiet': 
			$cfg['verbose'] =0;
			break;

		case '-h':
		case '-help':
			echo "options:\n"
				."\t-verbose n: set verbose level to n\n"
				."\n";
			break;
		}
}



$Indico =new INDICO( $cfg );
$Indico->load();

$errors =false;

$status_key =$Indico->request( '/event/{id}/editing/api/slides/list' );
//print_r( $Indico->data[$status_key] );


$status =[];
foreach ($Indico->data[$status_key] as $x) {
    if (!empty($x['editable']) && $x['editable']['state'] == "accepted") {
//        print_r( $x ); return;
        $s =$Indico->request( "/event/{id}/contributions/$x[id]/editing/slides", 
            'GET', false, [ 'return_data' =>true, 'quiet' =>true ]);

        print_r( $s ); return;
    }
}

return;

foreach ($Indico->data['papers'] as $pcode =>$p) {
    if ($p['status'] == 'g') {
        $Indico->verbose( "$pcode..", 1, false );

        $cmd ="pdffonts $cfg[data_path]/papers/$pcode.pdf";
        $pdffonts =false;
        exec( $cmd, $pdffonts );

        $fail =false;
        $fail_n =0;
        $l =0;
        foreach ($pdffonts as $line) {
            if ($l > 1) {
                $a =preg_split('/\s\s+/', str_replace( 'no ', 'no', $line ));
                $b =preg_split('/\s/', (count($a) == 6 ? $a[3] : $a[2] ));
                
                if (trim($b[0]) == 'no') {
                    $fail =true;
                    $fail_n ++;
                    $Indico->verbose2( "\n\t$line ($b[0])", 3, false );

                } else if (trim($b[0]) != 'yes') {
                    $Indico->verbose2( "\n\tWARNING\t$line ($b[0]) [$a[0]]", 2, false );
                }
            }
            
            $l ++;
        }
        
        if ($fail) {
            $Indico->verbose_next( "\n\tERROR ($fail_n fonts not embedded) ", false );
            $errors[$pcode][] ="fonts not embedded ($fail_n)";

        } else {
            $Indico->verbose_next( ".", false );
        }

        $cmd ="pdfinfo $cfg[data_path]/papers/$pcode.pdf";
        $pdfinfo =false;
        exec( $cmd, $pdfinfo );
        
        $info =false;
        foreach ($pdfinfo as $line) {
            $a =strpos( $line, ':' );
            $key =trim(strtolower(substr( $line, 0, $a )));
            $val =trim(strtolower(substr( $line, $a +1 )));
            $info[ strtr($key, ' ', '_' )] =$val;
        }
        
        $fail =false;

        if ($info['page_size'] != '595 x 792 pts') {
            $Indico->verbose_next( "\n\tERROR (wrong page size $info[page_size]) ", false );
            $fail =true;
            $errors[$pcode][] ="wrong page size ($info[page_size])";
        }
        
        $page_limit =$p['poster'] ? 5 : 6;
        if ($info['pages'] > $page_limit) {
            $Indico->verbose_next( "\n\tERROR (too many pages $info[pages]) ", false );
            $fail =true;
            $errors[$pcode][] ="too many pages ($info[pages])";
        }

        if (!$fail) $Indico->verbose_next( '.', false );

        $Indico->verbose();
    }
}

if ($errors) file_write_json( "$cfg[data_path]/papers-problems.json", $errors );

?>