#!/usr/bin/php
<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.08.30 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_papers' );

for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-verbose': 
			$cfg['verbose'] =$argv[++$i]; 
			break;

		case '-quiet': 
			$cfg['verbose'] =0;
			break;

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
                $a =preg_split('/\s\s+/', $line );
                $b =preg_split('/\s/', $a[3] );

//                print_r( $a ); print_r( $b ); return;

                if ($b[0] != 'yes') {
                    $fail =true;
                    $fail_n ++;
                    //                    echo "$line\n";
                }
            }
            
            $l ++;
        }
        
        if ($fail) {
            $Indico->verbose_next( "ERROR ($fail_n fonts not embedded) ", false );
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
            $Indico->verbose_next( "ERROR (wrong page size $info[page_size]) ", false );
            $fail =true;
            $errors[$pcode][] ="wrong page size ($info[page_size])";
        }
        
        $page_limit =$p['poster'] ? 5 : 6;
        if ($info['pages'] > $page_limit) {
            $Indico->verbose_next( "ERROR (too many pages $info[pages]) ", false );
            $fail =true;
            $errors[$pcode][] ="too many pages ($info[pages])";
        }

        if (!$fail) $Indico->verbose_next( '.', false );

        $Indico->verbose();
    }
}

if ($errors) file_write_json( "$cfg[data_path]/papers-problems.json", $errors );

?>