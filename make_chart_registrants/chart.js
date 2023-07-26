google.load('visualization', '1', {packages: ['corechart']});

function drawVisualization() {
 var data = new google.visualization.DataTable();
 data.addColumn('date', 'Date');
 data.addColumn('number', 'Number of ${var}');
${addrow}

 new google.visualization.${chart_type}(document.getElementById('chart_${var}')).
	draw(data, {curveType: 'function', 
		width: ${width}, height: ${height}, legend: 'none', colors: ['${color1}'],
		vAxis: {title: '${var}'}}
		);
}

google.setOnLoadCallback(drawVisualization);
