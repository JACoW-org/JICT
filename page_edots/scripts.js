
/* bY Stefano.Deiuri@Elettra.Eu

2022.07.20 - update

*/

var cfg ={
	title: 'Paper Processing Status',
	mode: 'full',
	change_page_delay: 10, // seconds
	reload_data_delay: 120, // seconds	
	pages: 1,
	cols: 7,
	rows: 3
	};
	
var active_page =0;

var data_ts =0;

var edots =[];

var init ={
	page: true,
	edots: true,
	};

$(document).ready( function() {
	if (navigator.platform.indexOf('arm') != -1) {
		console.log( 'Enable SLOW mode!' )
		cfg.mode ='slow';
	}
		
	load_data();
	show_page();
	setInterval( update_clock, 500 );
	});

//---------------------------------------------------------------------------------------------
function show_page() {
	$('#timer').css( 'width', '100%' );
	$('#timer').animate( { width: 0 }, { duration: cfg.change_page_delay *1000, queue: false } );
	
	duration =500;
	
	if (cfg.pages > 1) {
		console.log( `Show page ${active_page}` );
		
		if (active_page) {
			switch (cfg.mode) {
				case 'slow':
					$(`div[page=${active_page}]`).hide();
					break;
					
				default:
					$(`div[page=${active_page}]`).fadeOut(duration);
			}
		}
		
		active_page ++;
		if (active_page == (cfg.pages+1)) active_page =1;

		switch (cfg.mode) {
			case 'slow':
				$(`div[page=${active_page}]`).show();
				break;

			default:
				$(`div[page=${active_page}]`).delay(duration).fadeIn(duration);
		}
		
		if (cfg.pages) $('#activepage').html( active_page ); 
	}

	setTimeout( show_page, cfg.change_page_delay *1000 );
}

//---------------------------------------------------------------------------------------------
function update_clock() {
 var d =new Date();
 $('#clock').html( `${pad(d.getHours())}:${pad(d.getMinutes())}<span style='color:#bbb;'>:${pad(d.getSeconds())}</span>` );
}

//---------------------------------------------------------------------------------------------
function load_data() {
	$.getJSON( 'get.php', { ts: data_ts } )
		.done(function(obj) {
			if (obj.error) {
				console.log( 'DATA ERROR!' );
				setTimeout( load_data, (cfg.reload_data_delay /2) *1000 );
				return;
			}
			
			console.log( 'Load data ' +obj.ts );
			
			if (init.page) {
				init.page =false;
				
				if (obj.cfg != undefined) {
					console.log( `Update configuration v${obj.cfg.version}` );
					for (id in obj.cfg) {
						cfg[id] =obj.cfg[id];
						console.log( `cfg.${id} =${obj.cfg[id]}` );
					}

					console.dir( cfg );
				}
				
				document.title =`${cfg.conf_name} ${cfg.title}`;
				$('#title').html( `<b>${cfg.conf_name}</b> ${cfg.title}` );

				if (cfg.qrcode) $('#qrcode').html( `<img src='${cfg.qrcode}' />` );

			}
			
			if (obj.cfg.version != cfg.version) {
				console.log( 'RELOAD PAGE!' );
				location.reload();
				return;
			}

			var edots_updated =false;
			
			if (obj.edots != undefined) {
				console.log( "  Process edots" );

				for (id in obj.edots) {
					var status =obj.edots[id];
					
					if (status == '') status ='nofiles';
					else if (status == 'qaok') status ='g';
					
					edots[id] =status;
					
					console.log( `    ${id.substring(1)} (${status})` );

					if (status == 'removed') {
//						init.edots =true;
					}
					
					edots_updated =true;
				}
			}

			data_ts =obj.ts;
			
			if (edots_updated || init.edots) {
				update_edots( edots );
			}
			
			setTimeout( load_data, cfg.reload_data_delay *1000 );
			})
			
		.fail(function(XMLHttpRequest, textStatus, errorThrown) {
			console.log( "load data FAIL!" );
			console.dir( XMLHttpRequest );
			console.dir( textStatus );
			console.dir( errorThrown );
			
			setTimeout( load_data, cfg.reload_data_delay *1000 );
		});		
		
}


//---------------------------------------------------------------------------------------------
function update_edots( obj ) {
	console.log( "Update edots" );
	
	if (init.edots) {
		init.edots =false;
		
		var i =0;
		var page =1;
		var html ='';
		var id;
		
		var dpp =cfg.cols *cfg.rows; // dot per page
	
		console.log( `   Draw dots (${cfg.cols} * ${cfg.rows} = ${dpp})` );
		dpp -=cfg.qrcode_cells;
		
		html ="<div class='page' page='1' style='display:block;'></div>";
		if (cfg.pages > 1) {
			for (i =0; i <cfg.pages; i++) {
				html +=`<div class='page' page='${i +2}'></div>`;
				
			}
		}
		$('#edots').html( html );

	
		var ids =[];
		for (paper_id in obj) {
			if (obj[paper_id] != 'removed') ids.push( paper_id );
		}

        var tdw =100/cfg.cols +'%';


		for (page =0; page <cfg.pages; page ++) {
			var id;
			html ="<table class='edots'>";
			for (var row =0; row <cfg.rows; row ++) {
				html +="<tr>";
				for (var col =0; col <cfg.cols; col ++) {
					i =row +(col *cfg.rows) +(dpp *page);
					id =ids[i];

					skip =col == cfg.cols-1 && row >cfg.rows -cfg.qrcode_cells -1;

					if (i <ids.length && !skip) html +=`<td class='b_${obj[id]}' id='${id}' width='${tdw}'>${id.substring(1)}</td>`;
					else html +="<td class='empty'>&nbsp;</td>";
				}
				html +="</tr>";
			}
			html +="</table>";
			
			$(`div[page=${page +1}]`).html( html );
		}
	
		var html ="<table class='legend'><tr>";
		for (var legend_item_name in cfg.legend) {
			html +=`<td class='b_${legend_item_name}'>${cfg.legend[legend_item_name]}</td>`;
		}
		html +="</tr></table>";
		$('#legend').html( html );
	
		if (cfg.pages > 1) {
			$('#activepage').html( 1 ); 
			$('#npages').html( `/${cfg.pages}` ); 
			
		} else {
			$('#pages').hide();
		}
	
	} else {
		for (var paper_id in obj) {
			var status =obj[paper_id];		
			$(`#${paper_id}`).attr( 'class', `b_${status}` );
		}						
	}
}	


//-----------------------------------------------------------------------------
function pad( number ) {
 if (number < 10) return '0' + number;
 return number;
}
