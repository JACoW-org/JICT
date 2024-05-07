#!/usr/bin/php
<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.09.15 - fix pdf download
2022.08.26 - fix extracting pdf_title
2022.08.19 - 1st version (use poppler-utils)

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );
$cfg =config();


for ($i =1; $i <count($argv); $i ++) {
	switch ($argv[$i]) {
		case '-v': 
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

		case '-c':
		case '-cleanup':
            echo "Remove temporary PDF files... ";
            system( "rm -f $cfg[out_path]/*.pdf" );
            echo "OK\n";

            echo "Remove temporary TXT files... ";
            system( "rm -f $cfg[out_path]/*.txt" );
            echo "OK\n";

            echo "Remove temporary JPG files... ";
            system( "rm -f ./images/*.jpg" );
            echo "OK\n";

            return;
            break;

		case '-help':
			echo "options:\n"
				."\t-cleanup: clear cached data\n"
				."\t-verbose n: set verbose level to n\n"
				."\n";
			break;
		}
}


$Indico =new INDICO( $cfg );
$Indico->load();

if (!file_exists( './images')) {
    echo "Create images folder... ";
    mkdir( 'images' );
    echo "OK\n";
}

$content =false;

$app_data =$Indico->data['data'];

$token =$Indico->cfg['indico_token'];

$n =0;
$pdf_n =0;
$pdf_size =0;

$now =time();

$Indico->verbose( "\nDownload PDF:", 1, false );

$yellow_to_green =[];

foreach ($Indico->data['papers'] as $pcode =>$p) {
    if ($p['status'] == 'g') {	
        $n ++;
        
        $pdf_fname ="$cfg[out_path]/$pcode.pdf";

        if (!file_exists( $pdf_fname ) || !empty($cfg['force']) || $p['status_ts'] > filemtime( $pdf_fname )) { 
            $Indico->verbose_next( " $pcode ($p[id])", false );

			$pdf_url =$Indico->get_pdf_url( $p['id'] );

			if (empty($pdf_url)) {
            	$Indico->verbose_next( "!", false );
                //print_r( $Indico->api ); exit;

			} else {
                $Indico->verbose_next( ".", false );
                
                $cmd ="wget -q -O $pdf_fname --header='Authorization: Bearer $token' $pdf_url; touch $pdf_fname";
                system( $cmd );

    //			echo "$cmd\n";

                $Indico->verbose_next( ".", false );
                $pdf_n ++;

                $pdf_size +=filesize( $pdf_fname );

                $cmd ="pdftotext $pdf_fname";
                system( $cmd );
                
                
                $nl =0;
                $tl =0;
                $pdf_title =false;
                $title_block =true;
                $head =false;
                
                foreach (explode( "\n", file_read( "$cfg[out_path]/$pcode.txt" ) ) as $line_nt) {
    //					$line =trim($line_nt);
                    $line =preg_replace( '/[^[:print:]]/', "", trim($line_nt));

                    if (empty($line)) {
                        // skip first blank lines

                    } else if (uppercase_rate( $line ) > 50 && $title_block) {
    //					if (!strpos($line,'.') && !strpos($line,',') && trim($line) != "" && $title_block) {
                        $pdf_title .=($pdf_title ? " " : false) .trim($line);
                        $tl ++;

                    } else {
                        $title_block =false;
                    }

                    if (strtolower(trim($line)) == 'abstract') break;

                    $head[] =$line;
                    $nl ++;
                }

                $inst_n =count($p['authors_by_inst']);
                $authors_n =count(explode( ',', $p['authors']));
                $h =min( 250 +$nl *($authors_n > 20 ? 40 : 20), 800 );
                if (!$h) $h =800;
                $Indico->verbose_next( ".", false );
                
                $cmd ="pdftoppm -f 1 -l 1 -scale-to 1600 -y 100 -H $h -gray -jpeg $cfg[out_path]/$pcode.pdf >./images/$pcode.jpg";
                system( $cmd );
                $Indico->verbose_next( ".", false );

                $app_data[$pcode]['id'] =$p['id'];
                $app_data[$pcode]['pdf_title'] =$pdf_title;
                $app_data[$pcode]['pdf_ts'] =$now;
                $app_data[$pcode]['title_lines'] =$tl;
                $app_data[$pcode]['head'] =$head;
			}
		}

        if ($p['revision_count'] > 1 && !empty($p['prev_status']) && $p['prev_status'] == 'y') {
            $yellow_to_green[$pcode] =$p['status_ts'];
        }
    }
}

$pdf_mb =round( $pdf_size /1024 /1024, 0 );
$Indico->verbose_next( " OK ($pdf_n files download / $pdf_mb MB)\n" );

$content .="<br /><small>$n papers</small>\n";

$Indico->data['data'] =$app_data;
$Indico->save_file( 'data', 'out_data', 'DATA', array( 'save_empty' =>true ));



//-----------------------------------------------------------------------------
function uppercase_rate( $_string ) {
	$strl =strlen($_string);
	$stringL =strtolower( $_string );

	$upper_n =0;
	for ($i =0; $i <$strl; $i ++) {
		if (substr($_string,$i,1) != substr($stringL,$i,1)) $upper_n ++;
	}

	$rate =$upper_n ? round( $upper_n*100/$strl, 0 ) : 0;

//	echo "\n[ $_string ($upper_n) $rate%]\n";

	return $rate;
}

?>
