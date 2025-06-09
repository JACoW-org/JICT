// 2017.05.15 bY Stefano.Deiuri@Elettra.Eu


//-----------------------------------------------------------------------------
function select_day( _day ) {
	document.location =script +(_day ? '?day=' +_day : '');
}


//-----------------------------------------------------------------------------
function select_session( _session ) {
	if (sync) return;
	document.location =script +'?day=' +day +(_session ? '&session=' +_session : '');
}


//-----------------------------------------------------------------------------
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


//-----------------------------------------------------------------------------
function select_poster( _poster ) {
	document.location =script +'?day=' +day +'&session=' +session +'&poster=' +_poster;
}


//-----------------------------------------------------------------------------
function change_poster_status( _id, _set_status ) {
	
	s =_set_status != undefined ? _set_status : poster_status[ _id ];
	obj =$( '#status' +_id );
	
	// state1 =(_id == 3) ? 'PhotoYes' : 'On';
	// state2 =(_id == 3) ? 'PhotoNo' : 'Off';
	
	if (s != 1) {
		poster_status[ _id ] =1;
		obj.attr( 'class', 'On' );
		// obj.attr( 'class', state1 );
		
	} else {
		poster_status[ _id ] =0;
		obj.attr( 'class', 'Off' );
		// obj.attr( 'class', state2 );
	}

//	console.log( 'set ' +_id +' to ' +poster_status[ _id ] );
	
	if (_id == 1) {
		change_poster_status( 0, s );
		change_poster_status( 2, s );
		// change_poster_status( 3, (s != 1 ? 1 : 0) );
	}
	
//	console.dir( poster_status );
}


//-----------------------------------------------------------------------------
function poster_comment() {
	comment =window.prompt( 'Comments', (comment ? comment : '') );
	if (comment) {
		obj =$( '#comment' );
		obj.html( comment );
		obj.attr( 'class', 'comment_set' );
	}
}


//-----------------------------------------------------------------------------
function poster_save( _next ) {
	// if (poster_status[0] == -1 || poster_status[1] == -1 || poster_status[2] == -1 || poster_status[3] == -1) {
	if (poster_status[0] == -1 || poster_status[1] == -1 || poster_status[2] == -1) {
		alert( 'Please set all flags!' );
		return;
	}

	document.location =`${script}?day=${day}&session=${session}&poster=${poster}&save=1`
		+'&status0=' +poster_status[0] 
		+'&status1=' +poster_status[1] 
		+'&status2=' +poster_status[2] 
		+'&status3=' +poster_status[3] 
		+(_next ? '&next=' +_next : '')
		+(comment ? '&comment=' +encodeURIComponent(comment).replace(/[!'()]/g, escape).replace(/\*/g, '%2A') : '')
		;
}


//-----------------------------------------------------------------------------
function poster_next( _next ) {
	document.location =`${script}?day=${day}&session=${session}&poster=${_next}`;
}


//-----------------------------------------------------------------------------
function poster_close() {
	document.location =`${script}?day=${day}&session=${session}`;
}

//-----------------------------------------------------------------------------
async function delete_picture( _pid, _pts ) {
	if (!window.confirm( "Are you sure?" )) return;

	try {
		const response =await fetch( `${script}?cmd=delete_picture&session=${session}&poster=${poster}`,  { 
			method: 'POST', 
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body: new URLSearchParams({ picture_ts: _pts }) 
			});

		if (response.ok) {
			const result =await response.json();  

			console.log( 'Server result:', result );

			$(`#pic${_pid}`).hide();
			last_picture_ts =null;
		}

	} catch (error) {
        alert( 'Network error: ' + error.message );
        console.error( 'Network error:', error );
    }
}

//-----------------------------------------------------------------------------
async function upload_picture( _blob ) {
    if (!_blob) return false;

	console.log( 'Start uploading...' ); 

    const formData = new FormData();
    formData.append( 'picture', _blob, 'resized_image.jpg' );

    try {
		loadingSpinner.style.display = 'flex';
        const response =await fetch( `${script}?cmd=upload_picture&session=${session}&poster=${poster}`, 
            { method: 'POST', body: formData });

        if (response.ok) {
            const result =await response.json();  

			last_picture_ts =result.ts;

            console.log( 'Server result:', result );

            cameraInput.value ='';
            acquiredFile =null;

            poster_status[3] =1;

			// alert( 'Picture uploaded!' );
			loadingSpinner.style.display = 'none';
			pic0.style.display ='inline-block';

        } else {
            const errorText =await response.text();

            alert( 'Upload error: ' + errorText);
            console.error( 'Upload error:', response.status, errorText );
			loadingSpinner.style.display = 'none';
        }

    } catch (error) {
        alert( 'Network error: ' + error.message );
        console.error( 'Network error:', error );
		loadingSpinner.style.display = 'none';
    }
}



const MAX_WIDTH = 1200;  // Larghezza massima desiderata per l'immagine ridimensionata
const MAX_HEIGHT = 1200; // Altezza massima desiderata, per esempio
const QUALITY = 0.8;    // Qualità del JPG (da 0.0 a 1.0)

let acquiredFile = null;
let last_picture_ts =null;


//-----------------------------------------------------------------------------
function init_pictures() {
	const cameraInput = document.getElementById('cameraInput');
	const canvas = document.getElementById('canvas');
	const photoPreview = document.getElementById('photoPreview');
	const pic0 = document.getElementById('pic0');
	const loadingSpinner = document.getElementById('loadingSpinner');

	const ctx = canvas.getContext('2d');

	cameraInput.addEventListener('change', (event) => {
		const files =event.target.files;

		if (files.length > 0) {
			acquiredFile =files[0];

			const img =new Image();
			const reader =new FileReader();

			reader.onload = (e) => {
				img.src =e.target.result;
				//photoPreview.src =e.target.result;
				console.log( "Picture ready for upload:", acquiredFile.name, acquiredFile.type, acquiredFile.size);
				};

			img.onload = () => {
				// Calcola le nuove dimensioni mantenendo le proporzioni
				let width = img.width;
				let height = img.height;

				console.log( `Immagine size: ${width.toFixed(0)} x ${height.toFixed(0)}` );

				if (width > height) {
					if (width > MAX_WIDTH) {
						height *= MAX_WIDTH / width;
						width = MAX_WIDTH;
					}
				} else {
					if (height > MAX_HEIGHT) {
						width *= MAX_HEIGHT / height;
						height = MAX_HEIGHT;
					}
				}

				// Imposta le dimensioni del canvas alle nuove dimensioni
				canvas.width = width;
				canvas.height = height;

				// Disegna l'immagine sul canvas con le nuove dimensioni
				ctx.drawImage( img, 0, 0, width, height );

				// Ottieni l'immagine ridimensionata come Blob
				canvas.toBlob((blob) => {
					if (blob) {
						// resizedBlob =blob;
						photoPreview.src =URL.createObjectURL( blob ); // Mostra l'anteprima
						photoPreview.style.display ='block';
						console.log( `Immagine ridimensionata a ${width.toFixed(0)} x ${height.toFixed(0)} pixel. Pronta per l'invio.` );

						upload_picture( blob );

					} else {
						console.log( 'Errore nella creazione del Blob ridimensionato.' );
					}

				}, 'image/jpeg', QUALITY); // Formato e qualità dell'output


			};

			reader.readAsDataURL( acquiredFile );

		} else {
			acquiredFile = null; // Nessun file selezionato
			pic0.style.display = 'none';
		}
	});
}

function take_picture() {
	if (last_picture_ts == null) $("#cameraInput").click();
	else alert( "Please delete last picture taken before upload a new one" );
}