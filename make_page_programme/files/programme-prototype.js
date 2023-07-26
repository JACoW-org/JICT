var sopen =false;
var aopen =false;

//-----------------------------------------------------------------------------
function ms( _s, _all ) {

	console.log( `#${_s}` );

	if (aopen) {
		$( `#${aopen}` ).hide();
		$( `#${aopen}` ).html();
		aopen =false;
	}

	x =_all.split(',');

	if (sopen == _s) _s =false;
	for (i =0; i <x.length; i ++) {
		if (x[i] != 'EMPTY') {
			if (x[i] == _s) $( `#${_s}` ).show();
			else $( `#${_s}` ).hide();
		}
	}
	sopen =_s;
}

//-----------------------------------------------------------------------------
function ab( _a ) {
	if (aopen) {
		$( `#${aopen}` ).hide();
		$( `#${aopen}` ).html();

		if (aopen == _a) {
			aopen =false;
			return;
		}
	}

	aopen =_a;

	console.log( `Abstract (${_a}): /modules/mod_cws/abstracts/abstract.${aopen}.html` );

	$( `#${aopen}` ).show();


	$( `#${aopen}` ).load( `/modules/mod_cws/abstracts/abstract.${aopen}.html` );
}
