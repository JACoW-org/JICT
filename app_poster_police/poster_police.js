// 2017.05.15 bY Stefano.Deiuri@Elettra.Eu

function select_day( _day ) {
	document.location =script +(_day ? '?day=' +_day : '');
}

function select_session( _session ) {
	if (sync) return;
	document.location =script +'?day=' +day +(_session ? '&session=' +_session : '');
}

function session_sync( _session ) {
	if (sync == false) {
		ww =$(window).width();
		wh =$(window).height();

		$('#sync_box').css( 'width', ww/2 );
		$('#sync_box').css( 'left', ww/4 );
		$('#sync_box').css( 'top', wh/4 );
		
		$('#sync_bkg').css( 'display', 'block' );

		$('#sync_bkg').click( function() { return; } );

		sync =true;
	}
	
	$.getJSON( script, { cmd: 'session_sync', session: _session } )
		.done(function(obj) {
			console.log( 'session_sync.done ' +obj.pcode +'... ' +obj.status );
			$('#sync_bar').css( 'background-image', 'url(1px.png)' );
			$('#sync_bar').css( 'background-repeat', 'no-repeat' );
			$('#sync_bar').css( 'background-size', obj.percent +'% 100%' );
			$('#sync_bar').html( obj.percent +'%' );
			$('#sync_log').html( obj.pcode +' (' +obj.status +')' );
			
			if (obj.percent == 100) {
				$('#sync_title').html( 'Session synced!' );
									
				log =obj.tpc +' posters\' data synced!';
				if (obj.errors > 100) log +=' (' +obj.errors +' errors)';
				$('#sync_log').html( log );
				
				sync =false;
				$('#sync_bkg').click( function() { document.location =script +'?day=' +day } );
				
			} else {
				session_sync( _session );
			}
			
			})
		.fail(function() {
			console.log( 'session_sync.fail ' +script );
			});		
	
}

function select_poster( _poster ) {
	document.location =script +'?day=' +day +'&session=' +session +'&poster=' +_poster;
}

function change_poster_status( _id, _set_status ) {
	
	s =_set_status != undefined ? _set_status : poster_status[ _id ];
	obj =$( '#status' +_id );
	
	state1 =(_id == 3) ? 'PhotoYes' : 'On';
	state2 =(_id == 3) ? 'PhotoNo' : 'Off';
	
	if (s != 1) {
		poster_status[ _id ] =1;
		obj.attr( 'class', state1 );
		
	} else {
		poster_status[ _id ] =0;
		obj.attr( 'class', state2 );
	}

//	console.log( 'set ' +_id +' to ' +poster_status[ _id ] );
	
	if (_id == 1) {
		change_poster_status( 0, s );
		change_poster_status( 2, s );
		change_poster_status( 3, (s != 1 ? 1 : 0) );
	}
	
//	console.dir( poster_status );
}

function poster_comment() {
	comment =window.prompt( 'Comments', (comment ? comment : '') );
	if (comment) {
		obj =$( '#comment' );
		obj.html( comment );
		obj.attr( 'class', 'comment_set' );
	}
}

function poster_save( _next ) {
	if (poster_status[0] == -1 || poster_status[1] == -1 || poster_status[2] == -1 || poster_status[3] == -1) {
		alert( 'Please set all flags!' );
		return;
	}

	document.location =script +'?day=' +day +'&session=' +session +'&poster=' +poster +'&save=1'
		+'&status0=' +poster_status[0] 
		+'&status1=' +poster_status[1] 
		+'&status2=' +poster_status[2] 
		+'&status3=' +poster_status[3] 
		+(_next ? '&next=' +_next : '')
		+(comment ? '&comment=' +encodeURIComponent(comment).replace(/[!'()]/g, escape).replace(/\*/g, '%2A') : '')
		;
}

function poster_next( _next ) {
	document.location =script +'?day=' +day +'&session=' +session +'&poster=' +_next;
}

function poster_close() {
	document.location =script +'?day=' +day +'&session=' +session;
}
