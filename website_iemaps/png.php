<?

// 2014.12.09 by Stefano.Deiuri@Elettra.Eu

require_once( '../libs/jacow-1.0.lib.php' );
require_once( '../conference.php' );

define( 'XTRACT_PROC', '/xtract.getBoothStatus' );
define( 'BOOTH_W', 28 );
define( 'BOOTH_H', 28 );

$mdcs =array( '0' =>13 );

$fontsize =5;
$fontwidth =ImageFontWidth( $fontsize );
$tw =14;

$f =isset($_GET['f']) ? $_GET['f'] : '0';
$mdc =$mdcs[$f]; // MetaDataCode

$url =SPMS_URL . XTRACT_PROC .'?chk=' .PASSPHRASE .'&mdc=' .$mdc;

$floor ='floor' .$f;

// coordinates of booths
$txt_fname  =DATA_PATH ."/iemaps/${floor}.txt";
// coordinates of booths processed from txt_fname
$json_fname =DATA_PATH ."/iemaps/${floor}.json";
// input png map
$pngi_fname =DATA_PATH ."/iemaps/${floor}src.png";
// output png map
$pngo_fname =OUT_PATH ."/iemaps-${floor}.png";
// dump of SPMS XTRACT_PROC response
if (!file_exists( TMP_PATH .'/iemaps' )) mkdir( TMP_PATH .'/iemaps' );
$resp_fname =TMP_PATH ."/iemaps/${floor}.response";

$areas =file_read_json( $json_fname );
if (!$areas) {
	foreach (file( $txt_fname ) as $line) {
		list( $coords, $booths ) =explode( '|', trim($line) );
		list( $x, $y ) =explode( ',', $coords );
		
		foreach (explode( ',', $booths ) as $n) {
			$areas[$n] =array( $x, $y, $x +BOOTH_W, $y +BOOTH_H );
			$x +=BOOTH_W;
		}
	}
	ksort( $areas );
	file_write_json( $json_fname, $areas );
}


//$response =explode( ',', "6,8,7,8,40" );
$response =file( $url );

$status =array();
if ($response) {
	file_write( $resp_fname, implode( "\n", $response ));
	foreach ($response as $line) {
		$line = implode('',array_slice(explode(';', $line),1,1)); 
		if (preg_match_all( "/\d+/", $line, $match )) {
			$status =array_merge( $status, $match[0] );
		}
	}
}


$png =ImageCreateFromPNG( $pngi_fname );

$red   =imagecolorallocate( $png, 255, 0, 0 );
$pink  =imagecolorallocate( $png, 255, 150, 150 );
$blue  =imagecolorallocate( $png, 0, 0, 255 );
$cyan  =imagecolorallocate( $png, 150, 150, 255 );
$black =imagecolorallocate( $png, 0, 0, 0 );
$white =imagecolorallocate( $png, 255, 255, 255 );


foreach ($areas as $id =>$coords) {
	list( $x1, $y1, $x2, $y2 ) =$coords;

	$direction =($x2 -$x1) < ($y2 -$y1);
	
	if (in_array( $id, $status )) {
		ImageFilledRectangle( $png, $x1, $y1, $x2, $y2, $pink );

		if ($direction) {
			ImageLine( $png, $x1+1, $y1+1, $x2-1, $y2-1, $white );
			ImageLine( $png, $x1+2, $y1+1, $x2, $y2-1, $white );
			ImageLine( $png, $x1, $y1+1, $x2-2, $y2-1, $white );
		} else {
			ImageLine( $png, $x1+1, $y2-1, $x2-1, $y1+1, $white );
			ImageLine( $png, $x1+1, $y2-2, $x2-1, $y1, $white );
			ImageLine( $png, $x1+1, $y2, $x2-1, $y1+2, $white );
		}

		ImageRectangle( $png, $x1, $y1, $x2, $y2, $red );
//		ImageRectangle( $png, $x1+1, $y1+1, $x2-1, $y2-1, $red );
		$textcolor =$black;
		
	} else {
		ImageFilledRectangle( $png, $x1, $y1, $x2, $y2, $cyan );
		ImageRectangle( $png, $x1, $y1, $x2, $y2, $blue );
		$textcolor =$white;
	}
	
	$th =$fontwidth *($id > 9 ? 2 : 1);

	if ($direction) {
		ImageStringUp( $png, $fontsize, ($x1+$x2-$tw)/2, ($y1+$y2+$th)/2, $id, $textcolor );
	} else {
		ImageString( $png, $fontsize, ($x1+$x2-$th)/2, ($y1+$y2-$tw)/2, $id, $textcolor );
	}
}

header( 'Content-Type: image/png' );
ImagePNG( $png, $pngo_fname );
readfile( $pngo_fname );

?>
