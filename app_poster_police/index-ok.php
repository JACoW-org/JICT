<?php

/* by Stefano.Deiuri@Elettra.Eu

2025.06.01 - now is possible to delete the pictures
2025.05.31 - take picture fron the app
2023.04.04 - use Indico oauth
2022.08.29 - save out_posters_status

*/

/* 

https://www.digitalocean.com/community/tutorials/front-and-rear-camera-access-with-javascripts-getusermedia
https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia
https://developer.mozilla.org/en-US/docs/Web/API/Media_Streams_API/Taking_still_photos

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

//session_start();

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class PosterPolice extends JICT_OBJ {
 var $PP; // PosterPolicy
 var $PPS; // PosterPolicyStatus
 var $cfg;
 
 //-----------------------------------------------------------------------------
 function __construct( $_cfg =false, $_load =false ) {
	$this->PP =false;
	$this->PPS =false;
		
	$this->day =$_GET['day'] ?? false;
	$this->session =$_GET['session'] ?? false;
	$this->poster =$_GET['poster'] ?? false;

	$this->cfg =[ 
		'pps_fname' =>false
		];

	parent::__construct( $_cfg, $_load );
 }
 
 //-----------------------------------------------------------------------------
 public function config( $_var =false, $_val =false ) {
	parent::config( $_var, $_val );

	if ($this->cfg['dummy_mode']) $this->cfg['sync_url'] ='http://' .$_SERVER['SERVER_NAME'] .str_replace( 'index.php', 'dummy_sync.php', $_SERVER['PHP_SELF'] );
//	if ($_cfg && $this->cfg['dummy_mode']) $this->cfg['sync_url'] ='dummy_sync.php';
 }
 
 //-----------------------------------------------------------------------------
 function draw_begin() {
	$day =$this->day;
	$session =$this->session;
	$poster =$this->poster;

	$poster_vars =false;

	if ($poster) {
		$s =$this->get_status();
//		array_pop( $s );
		$comment =$s ? addslashes( $s[4] ) : '';
		$status =$s ? "$s[0],$s[1],$s[2],$s[3]" : '-1,-1,-1,-1';
	
		$poster_vars ="var poster ='$poster';\n"
			."\tvar poster_status =[ $status ];\n"
			."\tvar comment ='$comment';";
	}
	
	echo "
<html>
<head>
	<meta name='_viewport' content='width=device-width, initial-scale=1.0'>
	<meta name='mobile-web-app-capable' content='yes'>

	<title>Poster Police - " .CONF_NAME ."</title>

	<link href='https://fonts.googleapis.com/css?family=Lato:300' rel='stylesheet' type='text/css'>
	<link href='style.css?20250604a' rel='stylesheet' type='text/css' />	

	<script src='../dist/jquery-3.4.1/jquery.min.js'></script>
	
	<script>
	var script ='$_SERVER[PHP_SELF]';
	var sync =false;
    var usecamera =true;
	var day ='$day';
	var session ='$session';
	$poster_vars
	</script>
	
	<script src='poster_police.js?20250604b'></script>
</head>

<body>
<div id='sync_bkg' style='display:none'>
	<div id='sync_box'>
		<div id='sync_title'>Syncing...</div>
		<div id='sync_bar'>0%</div>
		<div id='sync_log'>...</div>
	</div>
</div>";

	if ($this->cfg['dummy_mode']) echo "<div class='warning2'>Dummy mode enabled!</div>\n";
 }
 
 
 //-----------------------------------------------------------------------------
 function draw_end() {
	global $user;

	echo "
<p id='user'>you are logged as: <strong>$user[email]</strong></p>
</body>
</html>
";
 }

  //-----------------------------------------------------------------------------
  function upload_picture() {
	$response = [
		'success' => false,
		'message' => 'Unknown error',
		'fileName' => null,
		'fileSize' => null,
		'fileType' => null,
		'ts' =>0
		];

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
			$file = $_FILES['picture'];

			$fileName = basename($file['name']); // Nome originale del file
			$fileTmpName = $file['tmp_name'];  // Percorso temporaneo sul server
			$fileSize = $file['size'];         // Dimensione del file
			$fileType = $file['type'];         // Tipo MIME del file

			$ts =time() +$this->cfg['difftime_sec'];

			$fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
			$newFileName =sprintf( "%s%s-%s.%s", $this->session, $this->poster, $ts, $fileExt );
			$uploadPath = $this->cfg['data_path'] .'/pictures/' .$newFileName;

			if (move_uploaded_file($fileTmpName, $uploadPath)) {
				$response['success'] = true;
				$response['message'] = 'Upload ok!';
				$response['fileName'] = $newFileName;
				$response['fileSize'] = $fileSize;
				$response['fileType'] = $fileType;
				$response['filePath'] = $uploadPath; // Percorso sul server
				$response['ts'] =$ts;
				
				$system_cmd =sprintf( "convert %s -resize x300 %s-small.jpg", $uploadPath, substr($uploadPath,0,-4) );
				$response['system_cmd'] =$system_cmd;

				system( $system_cmd );

			} else {
				$response['message'] = 'Error moving file';
			}

		} else {
			$response['message'] = 'No file: ' . ($_FILES['picture']['error'] ?? 'N/A');
		}
	}

	header('Content-Type: application/json'); // Assicurati di rispondere con JSON
	echo json_encode($response);
 }
 
  //-----------------------------------------------------------------------------
  function delete_picture() {
	$response = [
		'success' => false,
		'message' => 'Unknown error'
		];

	if (!empty($_POST['picture_ts'])) {
		$ts =(int)trim($_POST['picture_ts']);
		exec( sprintf( "ls -1 ./pictures/%s%s-%s*.jpg", $this->session, $this->poster, $ts ), $pictures_list );

		if (empty($pictures_list)) $response['message'] ='Files note found';
		else {
			$response['debug'] =$pictures_list;

			foreach ($pictures_list as $f) {
				$fname = basename($f);
				$safe_path = realpath( './pictures/' .$fname );

				$response['file_to_delete'][] =$safe_path;
				unlink( $safe_path );
			}
			
			$response['success'] =true;
			$response['message'] ='Pictures deleted';
		}
	}

	$response['POST'] =$_POST;
	$response['GET'] =$_GET;

	header('Content-Type: application/json'); // Assicurati di rispondere con JSON
	echo json_encode($response);
  }


 //-----------------------------------------------------------------------------
 function handle() {
	if (!empty($_GET['export'])) {
		$this->export();
		return;
	}
 
	$cmd =$_REQUEST['cmd'] ?? false;

    if ($cmd == 'upload_picture') {
		$this->upload_picture();
		return;
    }

    if ($cmd == 'delete_picture') {
		$this->delete_picture();
		return;
    }


	if ($cmd == 'session_sync') {
		list( $tp, $tpc, $errors, $pcode, $status )=$this->session_sync();
		$percent =round( $tpc * 100 / $tp );

		echo '{ "tp": "' .$tp .'", "tpc": "' .$tpc .'", "errors": "' .$errors .'", "percent": "' .$percent .'", "pcode": "' .$this->session .$pcode .'", "status": "' .$status .'" }';
		return;
	}
 
	if (!empty($_GET['save'])) {
		$this->save_status( $_GET['status0'], $_GET['status1'], $_GET['status2'], $_GET['status3'], $_GET['comment'] );
		if (!empty($_GET['next'])) {
			$this->poster =$_GET['next'];
//			$this->poster =str_pad( ++$this->poster, 3, '0', STR_PAD_LEFT );
		} else {
			$this->poster =0;
		}
	}
 
	$this->draw_begin();
	$this->select_day();
	$this->select_session();
	$this->select_poster();
	$this->draw_end();
 }

 
 //-----------------------------------------------------------------------------
 function export() {
	$csv_fname =str_replace( '/data/', '/tmp/', substr( $this->cfg['pps_fname'], 0, -5 )) .'.csv';
	$fp =fopen( $csv_fname, 'w' );
	
	$record =array( 'Poster Code', 'Abstract ID', 'Manned', 'Posted', 'Satisfactory', 'Picture', 'Comments' );
	fputcsv( $fp, array_values($record) );

	foreach ($this->PPS as $scode =>$so) {
		foreach ($so as $pcode =>$s) {
			$record =array(  $scode.$pcode, $s[5], $s[0], $s[1], $s[2], $s[3], $s[4] );
			fputcsv( $fp, array_values($record) );
		}
	}
	
	fclose( $fp );
	
	download_file( $csv_fname, CONF_NAME .'-PosterPolice.csv', 'application/excel' );
 } 
 
 
 //-----------------------------------------------------------------------------
 function session_sync() {
	set_time_limit( 60 );

	$sync_file =substr( $this->cfg['pps_fname'], 0, -5 ) .'-sync.json';
	
	if (file_exists( $sync_file )) {
		$sync =file_read_json( $sync_file, true );
	} else {
		foreach ($this->PPS as $pcode =>$s) {
			$sync[$pcode] =array( 'data' =>$s, 'sync_ts' =>false, 'sync_status' =>false );
		}
	}
	
	$sync_pcode =false;
	foreach ($sync as $pcode =>$x) {
		if (!$x['sync_ts'] && !$sync_pcode) {
			$s =$x['data'];
			$url =$this->cfg['sync_url']
				."?chk=" .SPMS_PASSPHRASE
				."&pid=" .$this->cfg['pp_manager']
				."&aid=$s[5]"
				."&pp=" .($s[1] ? 'Y' : 'N')
				."&pm=" .($s[0] ? 'Y' : 'N')
				."&ps=" .($s[2] ? 'Y' : 'N')
				."&pt=" .($s[3] ? 'Y' : 'N')
				."&co=" .urlencode($s[4]);

			$sync_status =trim(reset(file( $url )));

			if ($sync_status == '') $sync_status ='WD!';
			
			$x['sync_ts'] =time();
			$x['sync_status'] =$sync_status;
			
			$sync[$pcode] =$x;
			
			$sync_pcode =$pcode;
		}
	}
	
	$n =0;
	$ns =0; // synched
	$ne =0; // errors
	foreach ($sync as $pcode =>$x) {
		if ($x['sync_ts']) $ns ++;
		if ($x['sync_status'] != 'OK') $ne ++;
		$n ++;
	}
	
	file_write_json( $sync_file, $sync );
	
	if ($ns == $n) rename( $sync_file, substr( $sync_file, 0, -5 ) .'-' .date('U') .'.json' );
	
	return array( $n, $ns, $ne, $sync_pcode, $sync_status );
 } 
 
 
 //-----------------------------------------------------------------------------
 function select_day() {
	if ($this->day) {
		$day2 =date('D, j M Y',strtotime($this->day));
		echo "<div class='day_selected' onClick='select_day(false)'>$day2</div>\n";
		return;
	}
 
	echo "<h1 class='maintitle'>" .CONF_NAME ." Poster Police</h1>\n";
	foreach ($this->PP as $day =>$do) {
		$day2 =date('D, j M Y',strtotime($day));
		$today =$day == date('d-M-y');
		echo "<div class='day" .($today ? ' today' : false) ."' onClick='select_day(\"$day\")'>$day2</div>\n";
	}
 }
 
 
 //-----------------------------------------------------------------------------
 function session_stats( $_code =false ) {
	$sess =$_code ? $_code : $this->session;
 	$tp =count( $this->PP[$this->day][$sess]['posters'] );
	
	if ($_code) {
		$this->cfg['pps_fname'] =APP_DATA_PATH .'/' .$_code .'.json';
		if (file_exists( $this->cfg['pps_fname'] )) {
			$this->PPS =file_read_json( $this->cfg['pps_fname'], true );
		} else {
			return array( $tp, 0, 0 );
		}
	}
	
	$tpc =count( $this->PPS );
	$percent =round( $tpc * 100 / $tp );
	
	$sync_file =substr( $this->cfg['pps_fname'], 0, -5 ) .'-sync.json';
	$sync_pending =file_exists($sync_file);

	$last_sync =false;
	if (!$sync_pending) {
		$ls =exec( "ls -1tr " .APP_DATA_PATH .'/' .$_code .'-*.json'."" );
		if ($ls) {
			$ts =substr( $ls, strrpos( $ls, '-' ) +1, -5 );
			$last_sync =date( 'j M Y H:i', $ts );
		}
	}
	
	return array( $tp, $tpc, $percent, $last_sync, $sync_pending );
 }
 
 
 //-----------------------------------------------------------------------------
 function select_session() {


	if ($this->session) {
		list( $tp, $tpc, $percent ) =$this->session_stats();
		
		$style ="background-image: url(1px.png); background-repeat: no-repeat; background-size: $percent% 100%;";
		echo "<div class='session_selected' onClick='select_session(false)' style='$style'>" .$this->PP[$this->day][$this->session]['location'] .' (' .$this->session  .')'
			."<span style='float: right'>$tpc / $tp</span></div>\n"
			."</div>\n";

		return;
	}

	if (!isset($this->PP[$this->day])) return;

	foreach ($this->PP[$this->day] as $code =>$co) {
		if (empty($this->cfg['hide_sessions']) || !in_array( $code, $this->cfg['hide_sessions'] )) {
			@list( $tp, $tpc, $percent, $last_sync, $sync_pending ) =$this->session_stats( $code );
			
            $style ="background-image: url(1px.png); background-repeat: no-repeat; background-size: $percent% 100%;";

			$percent =0;
            
            $sync_button =($sync_pending ? 'Finish sync' : 'Sync')
                .($last_sync ? "<div class='last_sync'>last sync: $last_sync</div>" : false);

            echo "<div class='session' onClick='select_session(\"$code\")' style='$style'>$co[location] ($code)" 
                .($percent >= 100 ? "<div class='syncbutton' onClick='session_sync(\"$code\"); event.stopPropagation();'>$sync_button</div>" : false) 
                ."</div>\n";

		}
	}
 } 
 
 
 //-----------------------------------------------------------------------------
 function get_status( $_code =false ) {
	$pc =($_code ? $_code : $this->poster); // PosterCode
	if (!isset( $this->PPS[$pc])) return false;
	return $this->PPS[$pc];
 }
 
 
 //-----------------------------------------------------------------------------
 function select_poster() {
    $sid =$this->session;

	$posters =&$this->PP[$this->day][$sid]['posters'];

	if ($this->poster) {
		$read_only =!empty($_GET['read_only']);

		$p =$posters[$this->poster];
		
		$s =$this->get_status();
		if ($s) {
			$class0  =$s[0] ? 'On' : 'Off';
			$class1  =$s[1] ? 'On' : 'Off';
			$class2  =$s[2] ? 'On' : 'Off';
			// $class3  =$s[3] ? 'PhotoYes' : 'PhotoNo';
			$class_comment =$s[4] ? 'comment_set' : 'comment';
			$comment =$s[4] ? $s[4] : 'Comments';
			
		} else {
			$class0 =$class1 =$class2 =$class3 ='Switch';
			$class_comment ='comment';
			$comment ='Comments';
		}
		
		$next =true;
		
		$posterslist =array_keys( $posters );
		while (current($posterslist) != $this->poster && $next) $next =next($posterslist);
		$next =next($posterslist);
		

        $pid =$this->poster;
        $id =$sid .$pid;

        $pictures ="";

        // $pictures_list =scandir( $this->cfg['data_path'] .'/pictures/', SCANDIR_SORT_DESCENDING );
		exec( sprintf( "ls -1 ./pictures/%s*small*", $id ), $pictures_list );
		$pic_id =1;
        foreach ($pictures_list as $x) {
			$caption =preg_match( "/$id-(\\d+)-small\\.jpg/", $x, $matches)
				? date( "d/m H:i", $matches[1] )
				: false
				; 

			$xp =explode( '-', $x );

			$pictures .="
			<figure id='pic$pic_id'>
				<img src='$x' />
				<figcaption>
					" .($read_only ? false : "<button onClick='delete_picture($pic_id,$xp[1])'>Delete</button>") ."
					$caption
				</figcaption>
			</figure>    
			";

			$pic_id ++;
        }

        $pictures_cointainer ="
        <style>
			figure { display: inline-block; }
			img { height: 100%; max-height: 300px; border: 3px solid black; } 
			figcaption { margin-top: 5px; font-weight: bold; color: black; font-size: 2em; }
			figure button { font-size: 1em; width: 100%; height: 2em; display: block; margin-bottom: 0.5em; font-weight: 200; background: #ff8e8e; border: 3px solid black; }
			</style>
		
		<input type='file' accept='image/*' capture='environment' id='cameraInput' style='display: none;'>
		<canvas id='canvas' style='display:none;'></canvas>

		<div id='pictures_cointainer' style='border: none;'>
			$pictures
			<figure id='pic0' style='display: none;'>
				<img id='photoPreview' alt='New picture'>
				<figcaption>
					<button onClick='delete_picture(0,last_picture_ts)'>Delete</button>
					now
				</figcaption>
			</figure>    
			<div id='loadingSpinner'>
				<div class='spinner'></div>
				<p>Upload picture...</p>
			</div>

		</div>

        <script>
            const cameraInput = document.getElementById('cameraInput');
			const canvas = document.getElementById('canvas');
            const photoPreview = document.getElementById('photoPreview');
            const pic0 = document.getElementById('pic0');
			const loadingSpinner = document.getElementById('loadingSpinner');

            const ctx = canvas.getContext('2d');

        	let resizedBlob = null; // Per memorizzare il Blob dell'immagine ridimensionata

        	const MAX_WIDTH = 1200;  // Larghezza massima desiderata per l'immagine ridimensionata
        	const MAX_HEIGHT = 1200; // Altezza massima desiderata, per esempio
        	const QUALITY = 0.8;    // Qualità del JPG (da 0.0 a 1.0)
		
			let acquiredFile = null;
			let last_picture_ts =null;

            cameraInput.addEventListener('change', (event) => {
                const files = event.target.files;
                if (files.length > 0) {
                    acquiredFile =files[0];
                    const reader =new FileReader();

                    reader.onload = (e) => {
                        photoPreview.src =e.target.result;
                        console.log( \"Picture ready for upload:\", acquiredFile.name, acquiredFile.type, acquiredFile.size);
                        };

                    reader.readAsDataURL(acquiredFile);

                    save_picture();

                } else {
                    acquiredFile = null; // Nessun file selezionato
                    pic0.style.display = 'none';
                }
            });

			function take_picture() {
				if (last_picture_ts == null) $(\"#cameraInput\").click();
				else alert( \"Please delete last picture taken before upload a new one\" );
			}
        </script>";
        
		echo "<div class='poster_selected'>
			<h1>" .$this->poster ."</h1>
			<h1>$p[title]</h1>
			<h2>$p[presenter]</h2>
			<center>
			<div class='$class1' id='status1' onClick='change_poster_status(1)'>Posted</div>
			<div class='$class0' id='status0' onClick='change_poster_status(0)'>Manned</div>
			<div class='$class2' id='status2' onClick='change_poster_status(2)'>Satisfactory</div>

			$pictures_cointainer
			";

		if (!$read_only) echo "
			<br />
			<div class='comment' id='picture button' onClick='take_picture();'>Take a picture</div>
			<div class='$class_comment' id='comment' onClick='poster_comment()'>$comment</div>
			<br />
			<div class='button' onClick='poster_close()'>Close</div>
			<div class='button' onClick='poster_save()'>Save</div>"
			.($next ? "<div class='button' onClick='poster_save(\"$next\")'>Save & Next</div>". "<div class='button' onClick='poster_next(\"$next\")'>Skip</div>" : false)
			."</center>
			</div>\n";

		else echo "
			<div class='$class_comment'>$comment</div>
			";

		if ($read_only) echo "<script>document.body.style.pointerEvents = 'none';</script>";
		return;
	}
	
	if (!isset($posters)) return;
	
    $posters_with_pictures =[];
    $pictures_list =scandir( $this->cfg['data_path'] .'/pictures/', SCANDIR_SORT_DESCENDING );
    foreach ($pictures_list as $x) {
        if (strpos( $x, $sid ) !== false) {
            if (preg_match( "/$sid(\\d+)-*/", $x, $matches )) $posters_with_pictures[ $matches[1] ] =true;
        }
    }

	foreach ($posters as $code =>$po) {
		$s =$this->get_status( $code );
		
		if (!$s) $xclass =false;
		else if (!$s[0] && $s[1] && $s[2]) $xclass =' unmanned';
		else $xclass =$s[0] && $s[1] && $s[2] ? ' ok' : ' warning';

		echo "<div class='poster${xclass}' onClick='select_poster(\"$code\")'>$code" 
            .(!empty($posters_with_pictures[ $code ]) ? "<div class='bullet'></div>" : false)
            ."</div>\n";
	}


 }
 
 //-----------------------------------------------------------------------------
 function save( $_name =false ) {
	file_write_json( APP_PP, $this->PP );
 }
 
 
 //-----------------------------------------------------------------------------
 function save_status( $_s0, $_s1, $_s2, $_s3, $_comment ) {
	global $user;

	$aid =$this->PP[$this->day][$this->session]['posters'][$this->poster]['abstract_id'];
	$this->PPS[$this->poster] =[ $_s0, $_s1, $_s2, $_s3, $_comment, $aid, time(), $user['email'] ];

	file_write_json( $this->cfg['pps_fname'], $this->PPS );

    $this->update_status();
 }

 //-----------------------------------------------------------------------------
 function update_status() {
    $status =file_read_json( $this->cfg['out_posters_status'], true );

    foreach ($this->PPS as $pn =>$p) {
        $pid =$this->session .$pn;

        if ("$p[0]$p[1]$p[2]" =='111') $s ='OK';
        else if ("$p[0]$p[1]$p[2]" =='011') $s ='Unmanned';
        else if (empty($p[6])) $s ='Pending';
        else $s ='Fail';

        $status[$pid] =[
            'status' =>$s,
			'comment' =>$p[4],
			'picture' =>$p[3],
            'ts' =>$p[6]
			];
    }

    file_write_json( $this->cfg['out_posters_status'], $status );
 }

 //-----------------------------------------------------------------------------
 function load() {
	$this->PP =file_read_json( APP_IN_POSTERS, true );

	$pps_fname =($this->session ? APP_DATA_PATH .'/' .$this->session .'.json' : false);
	$this->cfg['pps_fname'] =$pps_fname;
	
	if (file_exists( $pps_fname )) {
		$this->PPS =file_read_json( $pps_fname, true );
		
	} else {
		$this->PPS =array();
	}
 }
}


$cfg =config( 'app_poster_police' );

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

$APP =new PosterPolice();
$APP->config( $cfg );






if (!need_file( APP_IN_POSTERS )) {
	echo_error( "\n\nTry to run spms_importer/make.php!" );
	die;
}

/* if (!APP_PASSWORD) {
	echo_error( "\n\nSet the login password in the config file!" );
	die;
}

if (!APP_PP_MANAGER) {
	echo_error( "\n\nSet the PosterPolice PersonID (pp_manager) in the config file!" );
	die;
} */

//$APP->check_auth();

$APP->load();

$APP->handle();

?>