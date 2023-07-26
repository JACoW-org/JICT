<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.10.26 - update 

*/

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

if (!$cfg =config( 'page_po_status', true )) {
//	echo json_encode(array( 'error' => true ));
	die;
}

$stats_db =file_read_json( APP_STATS, true );
$editors =file_read_json( APP_EDITORS, true );

//echo "<pre>";

//echo "<pre>" .print_r( $stats_db, true );

//$d =get_defined_constants(true); print_r($d['user']);


$editors_stats =false;
for ($i =0; $i <20; $i ++) {
	$editors_stats['processed'][$i] =0;
	$editors_stats['qa'][$i] =0;
}


$n_editor =0;
$papers_qa =0;
$papers_processed =0;

foreach ($editors as $e) {
	if (!empty($e['complete'])) {
		$n_editor ++;

		$c10 =floor($e['complete']/10);
		$editors_stats['processed'][$c10] ++;

		$papers_processed +=$e['complete'];
		$papers_qa +=$e['qa'];

		$qa10 =floor($e['qa']/10);
		$editors_stats['qa'][$qa10]  ++;
	}
}

//if ($_SERVER['REMOTE_ADDR'] == '140.105.2.32') echo "<pre>" .print_r( $stats_db, true );

$ts_start =strtotime( DATE_START .' 8:00' ); // +APP_DIFF_TZ *3600;
$ts_end =strtotime( DATE_END .' 23:00' ); // +APP_DIFF_TZ *3600;

$ts_data_start =$ts_start -(5 *86400);

$history =false;
$x0 =false;
foreach ($stats_db as $tm2 =>$x) {
	$t =explode( '-', $tm2 );
		
	$tts =mktime( $t[3], 0, 0, $t[1], $t[2], $t[0] );
//	$tts +=APP_DIFF_TZ *3600;

	if ($tts >= $ts_data_start && $tts <= $ts_end) {
		$tm2 =date( "Y-m-d-H", $tts );
	
		if (date( "H", $tts ) == '00' && $x0 == false) {
			$x0 =$x;
		
		} else if (date( "H", $tts ) == '23') {
			$dte =ceil(($tts -$ts_end) / 86400);
			if ($dte == '-0') $dte =0;
			
			$history[date( 'd M', $tts )] =array(
				'processed' =>$x['processed'] -$x0['processed'],
				'qaok' =>$x['qaok'] -$x0['qaok'],
				'dow' =>date( 'D', $tts ),
				'dte' =>$dte
				);
				
			$x0 =$x;
		}
	}

	$last_data =$x;
}	

$x0['edited'] =$papers_processed;
foreach(array("a", "g", "y", "r") as $key) unset($x0[$key]);

$x0['date'] =date( 'r', $x0['ts'] );



$export_fname ='../data/' .CONF_NAME .'-report.json';
$report =array( 
	'numbers' =>$x0, 
	'history' =>$history, 
	'editors' =>$editors_stats,
	'year' =>date( 'Y', $tts ), 
	'conf' =>CONF_NAME 
	);

file_write_json( $export_fname, $report );

$rows =false;
$rows['date'][] ='date';
$rows['date_dow'][] ='dow';
$rows['dte'][] ='day to end';
$rows['processed'][] ='processed';
$rows['qa'][] ='QA';

foreach ($history as $date =>$x) {
	$rows['date'][] =$date;
	$rows['date_dow'][] =$x['dow'];
	$rows['dte'][] =$x['dte'];
	$rows['processed'][] =$x['processed'];
	$rows['qa'][] =$x['qaok'];
}

$table_daily ="<h2>Daily Statistics</h2>\n<table>\n";
$r =0;
foreach ($rows as $rid =>$row) {
	$table_daily .="<tr>";
	$c =0;
	foreach ($row as $cell) {
		$tag =$c && $r ? 'td' : 'th';
		$table_daily .="<$tag>$cell</$tag>";		
		$c ++;
	}
	$table_daily .="</tr>";
	$r ++;
}
$table_daily .="</table>\n";



$rows =array(
	array( 'n papers' ),
	array( 'n editors (editing)' ),
	array( 'n editors (QA)' )
	);

$row_id =1;
foreach ($editors_stats as $type =>$x) {
	foreach ($x as $key =>$val) {
		if ($row_id == 1) $rows[0][] =$key *10 .'-' .($key+1)*10;
		$rows[$row_id][] =$val;
	}

	$row_id ++;
}

$table_editors ="<h2>Editors Statistics</h2>\n<table>\n";
foreach ($rows as $rid =>$row) {
	$table_editors .="<tr>";
	$c =0;
	foreach ($row as $cell) {
		$tag =$c ? 'td' : 'th';
		$table_editors .="<$tag>$cell</$tag>";		
		$c ++;
	}
	$table_editors .="</tr>\n";
}
$table_editors .="</table>\n";


$data =array(
	'Papers processed' =>$papers_processed,
	'Papers QA' =>$papers_qa,
	'Papers missing' =>$x0['nofiles'],
	'Papers removed' =>$x0['removed'],
	'Editors' =>$n_editor,
	'Papers/Editors' =>round( $papers_processed /$n_editor, 1),
	'QA/Editors' =>round( $papers_qa /$n_editor, 1 )
	);

$table_numbers ="<h2>Numbers</h2>\n<table>\n<tr>\n";
foreach ($data as $key =>$val) $table_numbers .="<th>$key</th>";
$table_numbers .="</tr>\n<tr>";
foreach ($data as $key =>$val) $table_numbers .="<td>$val</td>";
$table_numbers .="</tr>\n</table>\n";

$title =CONF_NAME .' Report';

?>

<html>
<head>
	<style>
		* { font-family: arial; }
		body { margin: 2em; }
		table { border: 1px solid silver; border-collapse: collapse; }
		table td, table th { border: 1px solid silver; padding: 5px; text-align: center; }
	</style>
	<title><?php echo $title; ?></title>
</head>

<body>
<h1><?php echo $title; ?></h1>

<?php echo $table_numbers; ?>

<?php echo $table_daily; ?>

<?php echo $table_editors; ?>

<p><br /><?php echo date('r', $x0['ts']); ?></p>
</body>
</html>
