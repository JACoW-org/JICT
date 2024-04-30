<?php

/* by Stefano.Deiuri@Elettra.Eu

2022.12.23 - skip withdrawn & show speaker
2022.12.07 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );

$abstracts =file_read_json( '../tmp/indico/event_41_manage_abstracts_abstracts.json', true );

$tbody =false;
$row =false;
foreach ($abstracts['abstracts'] as $x) {
    if ($x['state'] != 'withdrawn') {
        $ok =false;
        $authors =false;
        foreach ($x['persons'] as $p) {
            if (in_array($p['affiliation_link']['country_code'], ['RU','BY'])) {
                $ok =true;
                $authors .=($authors ? "\n<br />" : false) .sprintf( "%s %s - %s (%s) <span class='%s'>%s</span>", $p['first_name'], $p['last_name'], $p['affiliation'], $p['affiliation_link']['country_name'], $p['author_type'], $p['author_type'] );

                if ($p['is_speaker']) $authors .=" <span class='speaker'>speaker</span>";
            }
        }
        
        if ($ok) {
            if ($_GET['debug']) echo "<pre>" .print_r( $x, true ) ."</pre>";
    
            $row = [
                'id' =>sprintf( "<a href='%s/event/%d/abstracts/%d' target='_blank'>#%d</a>", 
                    $cws_config['global']['indico_server_url'], 
                    $cws_config['global']['indico_event_id'], 
                    $x['id'], 
                    $x['friendly_id'] ),
    
                'title' =>$x['title'],
    
                'authors' =>$authors
                ];
    
            $tbody .="<tr><td>" .implode( "</td>\n<td>", array_values($row) ) ."</td></tr>\n";
        }
    }
}

$title ='abstracts_from_ru_and_by';

$table ="
<table id='Abstracts' class='table table-bordered table-striped'>
<thead>
<tr>
<th>" .implode( "</th>\n<th>", array_keys( $row ) ) ."</th>
</tr>
</thead>
<tbody>
$tbody
</tbody>
</table>
";

$page ="
<html>
<head>
    <title>$title</title>

    <link rel='stylesheet' href='/cws/AdminLTE/bower_components/bootstrap/dist/css/bootstrap.min.css'>

    <!-- DataTables -->
    <link rel='stylesheet' href='/cws/AdminLTE/bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css'>  
    
    <!-- jQuery 3 -->
    <script src='/cws/AdminLTE/bower_components/jquery/dist/jquery.min.js'></script>
    
    <!-- Bootstrap 3.3.7 -->
    <script src='/cws/AdminLTE/bower_components/bootstrap/dist/js/bootstrap.min.js'></script>
    
    <!-- DataTables -->
    <script src='/cws/AdminLTE/bower_components/datatables.net/js/jquery.dataTables.min.js'></script>
    <script src='/cws/AdminLTE/bower_components/datatables.net/js/dataTables.buttons.min.js'></script>
    <script src='/cws/AdminLTE/bower_components/datatables.net/js/buttons.html5.min.js'></script>
    <script src='/cws/AdminLTE/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js'></script>
    <script src='/cws/AdminLTE/bower_components/jszip/jszip.min.js'></script>

    <style>
        #_Abstracts td:nth-child(2),
        #_Abstracts th:nth-child(2) { 
            display: none 
        }

        span.primary { background: blue; color: white; padding: 5px; font-size: 12px; border-radius: 5px; }
        span.secondary { background: #ddd; padding: 5px; font-size: 12px; border-radius: 5px; }
        span.speaker { background: orange; padding: 5px; font-size: 12px; border-radius: 5px; }
    </style>

</head>

<body>
<div style='padding:10px;'>
$table
</div>
<script>
$(function () {
    $('#Abstracts').DataTable({
      dom: \"<'row'<'col-sm-6'l><'col-sm-6'f>>\" +
      \"<'row'<'col-sm-12'tr>>\" +
      \"<'row'<'col-sm-6'i><'col-sm-6 text-right'B>>\",
    paging      : false,
    pageLength  : 10,
    lengthChange: true,
    searching   : true,
    ordering    : true,
    order       : [[ 0, 'asc' ]],
    info        : true,
    autoWidth   : false,
    buttons     : [
            { text: 'Export list as CSV', extend: 'csvHtml5', title: '$title' },
            { text: 'Export list as XLS (Excel)', extend: 'excelHtml5', title: '$title' }
            ]
        });
  })
</script>

</body>
<html>
";

echo $page;

?>