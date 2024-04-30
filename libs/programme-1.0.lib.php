<?php

/* by Stefano.Deiuri@Elettra.Eu & R.Mueller@gsi.de

2022.08.19 - new option: special_sessions_class

*/

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class Programme extends JICT_OBJ {
 var $abstracts;
 var $classes;
 var $programme;

 //-----------------------------------------------------------------------------
 function __construct() {
 	$this->abstracts =[];
 	$this->classes =[];
 	$this->programme =[];
	$this->programme['classes'] =[];	
	$this->programme['rooms'] =[];	
 }
 
 //-----------------------------------------------------------------------------
 function prepare() {
	if (!file_exists( OUT_PATH .'/programme.php' )) {
		$this->verbose( "Copy default files", 1 );
		exec( "cp -r files/* " .OUT_PATH );
	}
 }
 
 //-----------------------------------------------------------------------------
 function cleanup() {
	system( 'rm -f ' .APP_OUT_PATH .'/*.html' );
 } 
 
 //-----------------------------------------------------------------------------
 function load( $_programme =true, $_abstracts =true ) {
  	if ($_abstracts) {
		$this->abstracts =file_read_json( APP_ABSTRACTS, true );
	}
	
 	if ($_programme) {
		$this->programme =file_read_json( APP_PROGRAMME, true );
	}
 }
 
 //-----------------------------------------------------------------------------
 function make_rooms_css() {
	$colors =array( '#7FDBFF','#39CCCC','#3D9970','#2ECC40','#01FF70','#FFDC00','#FF851B','#FF4136','#85144b','#F012BE','#B10DC9','#AAAAAA','#DDDDDD' );
	 
	arsort( $this->programme['rooms'] );
//	print_r( $this->programme['rooms'] );

	echo "Make rooms css...\n";
	 
	$c =0;
	$css =false;
	foreach ($this->programme['rooms'] as $room =>$n) {
		if ($n) {
			$css .=sprintf( ".b_room%s { background: %s; }\n", $room, $colors[$c] );
			echo sprintf( "%s %s\n",  str_pad( $room, 20, '.', STR_PAD_RIGHT ), $colors[$c] );
		}
		$c ++;
	}

	echo "\n";
	
	$this->verbose( "\n# Write programme-rooms.css", 1 );
	file_write( OUT_PATH .'/programme-rooms.css', $css );
 }
	 
 //-----------------------------------------------------------------------------
 function make_abstracts() {
	$this->verbose( "\n# Save " .count($this->abstracts) ." abstracts... ", 1, false );

	foreach ($this->abstracts as $aid =>$A) {
		if (strlen($A['text']) > 10) {
			$this->verbose( "$aid ", 3, false );
			$fpa =fopen( APP_OUT_PATH ."/abstract.$aid.html", 'w' );
			
			$page =$A['text'] ."\n"
				.($A['footnote'] ? "<hr noshade size='1' width='60%' /><small>" .str_replace( '**', '<br />**', $A['footnote'] ) ."</small>\n" : false)
				.($A['agency'] ? "<hr noshade size='1' width='60%' /><small>$A[agency]</small>\n" : false)
				;			
			
			fwrite( $fpa, $page );
			fclose( $fpa );
		}
	}
	
	$this->verbose( "OK\n", 1 );
 }

 //-----------------------------------------------------------------------------
 function make_ics() {
//http://www.elettra.trieste.it/events/2012/ipac/programme/programme.ics

	$fp =fopen( APP_ICS, 'w' );

//	$timezone =";TZID=CDT";
	$timezone =false;
	$tz =false;
	
	fwrite( $fp, "BEGIN:VCALENDAR
METHOD:PUBLISH
X-WR-CALDESC:IPAC'12 Scientific Programme
X-WR-CALNAME:IPAC'12 Scientific Programme
PRODID:-//Stefano Deiuri/SPMS Programme 1.0//EN
VERSION:2.0
" );

/*
BEGIN:VTIMEZONE
TZID:CDT
BEGIN:DAYLIGHT
TZNAME:CDT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
END:DAYLIGHT
END:VTIMEZONE
" );
*/
	
//	$time_created =date('Ymd') .'T' .date('His') .$tz;
//	$time_updated =date('Ymd') .'T' .date('Hi') .'00' .$tz;
	$time_created =vevent_date( time() );
	$time_updated =$time_created;


	foreach ($this->programme['days'] as $day =>$sessions) {
		if (!$sessions) break;

		echo "\nDay $day ";

		ksort( $sessions );
		
//		$d =date_parse( $day );
//		$day2 =$d['year'] .str_pad( $d['month'], 2, '0', STR_PAD_LEFT ) .str_pad( $d['day'], 2, '0', STR_PAD_LEFT );

		foreach ($sessions as $id =>$S) {

			if (strpos($S['type'], 'poster') !== false) {
				$uid ='postersession-'.md5( print_r($S,true) );
				echo 'P';

//				$time_start ="${day2}T" .str_replace( ':', '', $S['time_from'] ) .'00' .$tz;
//				$time_end ="${day2}T" .str_replace( ':', '', $S['time_to'] ) .'00' .$tz;
				$time_start =vevent_date($S['tsz_from']);
				$time_end =vevent_date($S['tsz_to']);

				$event ="DTSTAMP:$time_created
LAST-MODIFIED:$time_created
UID:$uid@ipac12.org
DTSTART$timezone:$time_start
DTEND$timezone:$time_end
STATUS:CONFIRMED
SUMMARY:$S[code] Poster session
LOCATION:$S[location]
TRANSP:OPAQUE
DESCRIPTION:Poster session
";
				fwrite( $fp, str_replace( ',', '\,', "BEGIN:VEVENT\n" .$event ."END:VEVENT\n" ));

			} else {
				if (is_array($S['papers'])) {
					foreach ($S['papers'] as $pid =>$P) {
						if (strpos(strtolower($S['title']), 'poster') === false) {
							$uid ='event-' .md5( print_r($P,true) );
							echo '+';
					
//							print_r( $P );
					
							$timeto =$P['tsz_to'] ? $P['tsz_to'] : $S['tsz_to'];
							$time_start =vevent_date($P['tsz_from']);
							$time_end =vevent_date($timeto);
//							$time_start ="${day2}T" .str_replace( ':', '', $P['time_from'] ) .'00' .$tz;
//							$time_end ="${day2}T" .str_replace( ':', '', $timeto ) .'00' .$tz;
					
							if ($time_start != $time_end) $event ="DTSTAMP:$time_created
LAST-MODIFIED:$time_created
UID:$uid@ipac12.org
DTSTART$timezone:$time_start
DTEND$timezone:$time_end
STATUS:CONFIRMED
SUMMARY:$pid $P[title] ($P[author])
LOCATION:$S[location]
TRANSP:OPAQUE
DESCRIPTION:Session: $S[title]
";
							fwrite( $fp, str_replace( ',', '\,', "BEGIN:VEVENT\n" .$event ."END:VEVENT\n" ));
							
						} else {
							echo '-';
						}
					}
				}
			}
		}

	}

	fwrite( $fp, "END:VCALENDAR\n" );
	fclose( $fp );
	
	echo "\n\nWrite ics file.\n\n";
 }

 //-----------------------------------------------------------------------------
 function make() {
	$this->verbose();
	$this->verbose( "# Save day pages" );

	$SHTML =[]; // Sessions HTML
 
	if (!$this->classes) {
		$this->classes =$this->programme['classes'];
	}
	
	$days =array_keys($this->programme['days']);
 
	$dayn =0;
    $sidx =0; // session index

	foreach ($this->programme['days'] as $day =>$sessions) {
		$dayn ++;

		if (!$sessions) break;

		$this->verbose( "## day $dayn page ($day)", 2 );

		$fp_day =fopen( APP_OUT_PATH ."/day$dayn.html", 'w' );

		$menu =false;

		$dow =[ 'Fri', 'Thu', 'Wed', 'Tue', 'Mon' ];
		$daynt =0;
		foreach ($days as $d) {
			$daynt ++;
			$d2 =date( 'D, d M', strtotime( $d ));

			$sel =$day == $d;
			
            $href =$this->cfg['day_link_fmt'] 
                ? str_replace( array( '{day}', '{base_url}' ), array( $daynt, APP_BASE_URL ), $this->cfg['day_link_fmt'] )
                : APP_BASE_URL .(strpos( APP_BASE_URL, '?' ) ? '&' : '?') ."day=$daynt"
                ;

			$menu .="<td class='m" .($sel ? 's' : ' clickable') ."'"
                .($sel ? false : " onClick='document.location=\"$href\"'")
                .">$d2"
//				.($sel ? $d2 : "<a href='$href'>$d2</a>")
				."</td>\n";
		}

		fwrite( $fp_day, "<table class='day' " .APP_TAB_W ." cellpadding='3' cellspacing='0'>\n"
			."<tr class='days'>\n"
			."<td class='overview clickable' onClick='document.location=\"javascript:day(0);\"'>Overview</td>\n"
			.$menu
			."</tr>\n"
			."</table>\n"
			);
		
		
		$this->verbose( "\tSort sessions... ", 3, false );
		ksort( $sessions );
		$this->verbose( "OK", 3 );

		$ps =[]; // Paralel Sessions
		$ltf =false; // Last Time
		$lbc =false;

		foreach ($sessions as $id =>$S) {
            $sidx ++;

			$tf =empty($S['time_from']) ? false : $S['time_from'];

			if ($ltf && ($ltf != $tf)) {
				$page =$this->session( $ps, $sid, $SHTML );
				
				if (in_array( $tf, $this->cfg['coffee_break_time_end'])) $page .=$this->event( $lte, $tf, 'Coffee Break' );
				else if (in_array( $tf, $this->cfg['lunch_break_time_end'])) $page .=$this->event( $lte, $tf, 'Lunch Break' );

				fwrite( $fp_day, $page );

				$ps =[];
				$SHTML =[];
			}
			
//			print_r( $S );
			
			if ($id == '999999_END') break;
						
			$sid =$S['code'];
//            $sidb ="${sid}_${sidx}"; // Session id block
            $sidb ="S$sidx"; // Session id block
			$npapers =is_array($S['papers']) ? count($S['papers']) : false;

            $this->verbose( "\tSession $sid #$sidb ($S[time_from] > $S[time_to]) [$S[room]]" .($npapers ? "$npapers papers" : false), 3 );

            if (empty($this->cfg['sessions_details'])) $npapers =false;
	
			$code =empty($sid) ? false : "<div class='code'>$sid</div>";
//			$code ="<div class='code'>$sid</div>";
//			$times ="<span class='timeh'>($S[time_from]" .$this->img('DB') ."$S[time_to])</span>";
			$times ="<span class='timeh'>$S[time_from]  - $S[time_to]</span>";
	
			$timefrom =time2minutes($S['time_from']);
			$timeto =time2minutes($S['time_to']);

			if (!empty($this->cfg['special_sessions_class'][$sid])) $sclass =$this->cfg['special_sessions_class'][$sid];
			else $sclass ="b_room$S[room]";

			$SHTML["_$sidb"] =
				"<td width='##W##' room='$S[room]' timefrom='$timefrom' timeto='$timeto' class='$sclass session"
                .($npapers ? " clickable' onClick='javascript:ms(\"$sidb\",\"##OSID##\");'" : "'")
                .">$code " 
				.(!$S['poster_session'] && !empty($S['chair']) ? "<span class='chair'><i>Chair:</i>&nbsp;$S[chair]</span><br />" : false) 
//				.($npapers ? "<a href='javascript:ms(\"$sidb\",\"##OSID##\");'>" : false) 
				."$S[title]<br />$times</td>";
		
			$SHTML["{$sidb}_"]=		
				"<table id='$sidb' border='0' cellpadding='3' cellspacing='0' class='prg talks' " .APP_TAB_W ." style='display: none; border-top: none;' room='$S[room]' timefrom='$timefrom' timeto='$timeto'>\n"
				."<tr><td colspan='3' class='$sclass colorbar'></td></tr>\n"
//				."<tr><td colspan='##COLSPAN##' class='$sclass'>" .$this->img('SPh5') ."</td></tr>\n"
				;
		
			$SHTML[$sidb] =
				"<table border='0' cellpadding='3' cellspacing='0' class='prg'" .APP_TAB_W .">\n"
				."<tr><td colspan='3' class='$sclass session'>$code $S[title]"
				.(!empty($S['chair']) ? "<br /><span class='chair'><i>Chair:</i> $S[chair]</span> <span class='inst'>($S[chair_inst])</span>" : false)
				.($npapers ? false : "<br />$times") 
				."</td></tr>\n";

			$fst =true;
			if ($npapers) {
				foreach ($S['papers'] as $pid =>$P) {
					$rspan =" rowspan='$npapers'";

					$presenter =empty($P['presenter']) ? false : $P['presenter'];
					if (empty($presenter) && !empty($P['author'])) $presenter =$P['author'] .($P['author_inst'] ? " <span class='inst'>($P[author_inst])</span>" : false);

					$contrib_type =empty($P['poster']) ? 'talk' : 'poster';

					$timefrom =time2minutes($P['time_from']);
					$timeto =time2minutes($P['time_to']);

					$row ="<tr" .(empty($P['poster']) ? " timefrom='$timefrom' timeto='$timeto'" : false) .">" 
						.($fst ? "<td class='$sclass fst' $rspan>&nbsp;</td>" : false) 		
						.($P['time_from'] && empty($P['poster'])? "<td class='time' align='center' valign='top'>$P[time_from]" .$this->img('DA') ."</td>" : false)
						."<td" .($P['time_from'] ? "" : " colspan='2'") ." width='100%' class='paper $contrib_type'>"
							.(empty($P['code']) ? false : "<span class='code2'>$pid</span>")
							.($presenter ? "$presenter<br />" : false)
							."<b>"
								.(!empty($P['abstract']) ? "<a href='javascript:ab(\"$pid\");'>$P[title]</a>" : $P['title'])
								."</b>"
							."<div id='$pid' class='abstract'></div>"
							."</td></tr>\n";
				
					$SHTML[$sidb] .=$row;
					$SHTML["{$sidb}_"] .=$row;
		
					$fst =false;
				}
			}

			$SHTML["{$sidb}_"] .="</table>";
			$SHTML[$sidb] .="</table>";

			array_push( $ps, $sidb );
			$ltf =$S['time_from'];
			$lte =$S['time_to'];
		}

		fclose( $fp_day );
	}
 }

 //-----------------------------------------------------------------------------
 function session( &$ps, $sid, &$html ) {
	if (APP_SESSIONS == 'collapsed') return $this->multi_session( $ps, $html );
	 
	return (count($ps) == 1) ? $html["_${sid}"] : $this->multi_session( $ps, $html );
 }

 //-----------------------------------------------------------------------------
 function multi_session( $_codes, &$html ) {
	$width =round(100/count($_codes));

	$a =$b =false;

	foreach ($_codes as $c) {
		if ($c == 'EMPTY') {
			$a .="\t" .$html["_{$c}"] ."\n";

		} else {
			$a .="\t" .$html["_{$c}"] ."\n";
			$b .=$html["{$c}_"] ."\n";
		}
	}

	$a =str_replace( '##W##', "$width%", $a );
	$a =str_replace( '##OSID##', implode(',',$_codes), $a );
	$b =str_replace( '##COLSPAN##', (count($_codes) +1), $b );
	
	return "<table border='0' cellpadding='3' cellspacing='0' class='prg'" .APP_TAB_W ."><tr valign='top'>\n"
		.$a ."</tr></table>\n" .$b;
 }

 //-----------------------------------------------------------------------------
 function event( $_time_from, $_time_to, $_text, $_class ='event' ) {
	$timefrom =time2minutes($_time_from);
	$timeto =time2minutes($_time_to);

	return "<table border='0' cellpadding='3' cellspacing='0' class='$_class' timefrom='$timefrom' timeto='$timeto'" .APP_TAB_W ."><tr>"
//		."<th class='fst'>" .$this->img('SPw4') ."</th>"
		."<th class='fst'>" .$this->img('SHADE') ."</th>"
		."<td class='time'>$_time_from" .$this->img('DA') ."<br />$_time_to</td>"
		."<th width='100%'>$_text</th>"
		."</tr></table>";
 }

 //-----------------------------------------------------------------------------
 function img( $_type ) {
	switch ($_type) {
		case 'SHADE': 	return "&nbsp;";
		case 'DA':		return "<br /><i class='fa fa-caret-down'></i>";
		case 'DB':		return " <i class='fa fa-arrow-right'></i> ";
		case 'SPw4':	return "<i style='2em'></i>";
		case 'SPh5':	return "<i style='2.5em'></i>";
	}
 }
}



//-----------------------------------------------------------------------------
function vevent_date( $_date, $_z =true ) {
	return date('Ymd',$_date) .'T' .date('His',$_date) .($_z ? 'Z' : false);
}

//-----------------------------------------------------------------------------
function time2minutes( $_time ) {
	list( $h, $m ) =explode( ':', $_time );
	return $h*60 +$m;
}

?>
