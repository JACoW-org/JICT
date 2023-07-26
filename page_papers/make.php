#!/usr/bin/php
<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.05.10 - check barcode font
2022.09.15 - fix check fonts
2022.08.30 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'page_papers', false, false );

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

foreach ($Indico->data['papers'] as $pcode =>$p) {
    $pdf_fname ="$cfg[data_path]/papers/$pcode.pdf";

    if ($p['status'] == 'g' && file_exists($pdf_fname)) {
        $Indico->verbose( "$pcode..", 1, false );

        $cmd ="pdffonts $pdf_fname";
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

                if (substr( $line, 0, 16 ) == 'Free3of9Extended') $errors[$pcode][] ="BarCode or Cols Guides";
            }
            
            $l ++;
        }
        
        if ($fail) {
            $Indico->verbose_next( "\n\tERROR ($fail_n fonts not embedded) ", false );
            $errors[$pcode][] ="fonts not embedded ($fail_n)";

        } else {
            $Indico->verbose_next( ".", false );
        }

        $cmd ="pdfinfo $pdf_fname";
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

        if (empty($info['page_size'])) {
            $Indico->verbose_next( "\n\WARN (no info about page size) ", false );
            $fail =true;
            $errors[$pcode][] ="no info about page size";

        } else if ($info['page_size'] != '595 x 792 pts') {
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

if ($errors) {
    file_write_json( "$cfg[data_path]/papers-problems.json", $errors );

    print_r( $errors );
}

?>