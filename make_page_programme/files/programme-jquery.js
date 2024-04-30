var sopen =false;
var aopen =false;

function ms( _s, _all ) {
	if (aopen) {
		$( `#${aopen}` ).hide();
		$( `#${aopen}` ).html();
		aopen =false;
	}

	x =_all.split(',');

	if (sopen == _s) _s =false;
	for (i =0; i <x.length; i ++) {
		if (x[i] != 'EMPTY') {
			if (x[i] == _s) $( `#${x[i]}` ).show();
			else $( `#${x[i]}` ).hide();
		}
	}
	sopen =_s;
}

//-----------------------------------------------------------------------------
function day( _day ) {
	$( `#programme` ).load( `./programme/day${_day}.html` );
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

    a_url =`./programme/abstract.${aopen}.html`;
	console.log( `Abstract (${_a}): ${a_url}` );

	$( `#${aopen}` ).show();


	$( `#${aopen}` ).load( a_url );
}
