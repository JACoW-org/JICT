<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.03.20 - Papers charts
2022.12.02 - 1st version

*/

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'indico', '1.0' );

define( 'DAYS', 86400 );

$cfg =config( 'page_dashboard' );
$cfg['verbose'] =0;

$dates =$cws_config['global']['dates'];

$Indico =new INDICO( $cfg );
$Indico->load();

$user =$Indico->auth();
if (!$user) exit;


$T =new TMPL( 'template.html' );
$T->set([
    'style' =>'main { font-size: 14px; margin-bottom: 5em; }',
    'title' =>$cfg['name'],
    'logo' =>$cfg['logo'],
    'conf_name' =>$cfg['conf_name'],
    'user' =>__h( 'small', $user['email'] ),
    'path' =>'./',
    'scripts' =>false,
    'js' =>false
    ]);


$payments =import_payments();

$vars =[ 
    'page_title' =>APP_NAME, 
    'registered_n' =>0,
    'papers_n' =>0,
    'content' =>"",
    'js' =>""
    ];

$charts =[];


// PAPERS -------------------------------------------------------------------
$Indico->data['papers']['by_dates'] =[];
$Indico->data['papers']['by_days_to_deadline'] =[];
$last =0;
$papers_stats =[];

$ts_from =strtotime($dates['papers_submission']['from']);
$ts_deadline =strtotime($dates['papers_submission']['deadline']);

foreach ($Indico->data['stats'] as $ymdh =>$x) {
    if ($x['ts'] >= $ts_from) {
        $date =substr( $ymdh, 0, 10 );
        $papers_stats[$date] =$x;
    }
}

foreach ($papers_stats as $date =>$x) {
    $ts =$x['ts'];
    
//    $val =$x['total'] -$x['nofiles'] -$last;
    $val =$x['files'] +$x['processed'] -$last;
    if ($val < 0) $val =0;
    $Indico->data['papers']['by_dates'][$date] =$val;
    
    if ($val) {
        $ttd =($ts -$ts_deadline) /86400;
        $Indico->data['papers']['by_days_to_deadline'][$ttd] =$val;

        //$last =$val;
    }

    $last =$x['files'] +$x['processed'];
}

//print_r( $Indico->data['papers'] );

$group ='papers';
$id ='by_dates';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>ucwords($group) .' by day',
    'type' =>'bar',
    'y_label' =>$group,
    'series' =>false
    ];

$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME ."-$group", $Indico->data[$group][$id], 
    [ 'by_dates_show_zero' =>true, 'x_type' =>'date', 'x_upper_limit' =>strtotime($dates['papers_submission']['deadline']) +10*86400 ] );
    
$id ='by_days_to_deadline';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>ucwords($group) .' progress',
    'type' =>'scatter',
    'x_label' =>sprintf( 'days to deadline (%s)', substr( $dates['papers_submission']['deadline'], 0, 10 )),
    'y_label' =>$group,
    'series' =>false
    ];

$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME, $Indico->data[$group][$id], [ 'sum' =>true, 'x_low_limit' =>$dtd_limit, 'x_upper_limit' =>10 ] );
$vars[ $group .'_n' ] =number_format( $sum, 0, ',', '.' );;



// PAYMENTS -------------------------------------------------------------------

$group ='payments';
$id ='by_dates';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>ucwords($group) .' by day',
    'type' =>'bar',
    'y_label' =>$group,
    'series' =>false
    ];

$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME, $payments[$id]['count'], [ 'by_dates_show_zero' =>true, 'x_type' =>'date' ] );
    
$id ='by_days_to_deadline';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>ucwords($group) .' progress',
    'type' =>'scatter',
    'x_label' =>'days to deadline',
    'y_label' =>$group,
    'series' =>false
    ];

$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME,  $payments[$id]['count'], [ 'sum' =>true ] );
$vars['payments_n'] =number_format( $sum, 0, ',', '.' );;



// ABSTRACTS -------------------------------------------------------------------

$group ='abstracts_stats';
$id ='by_dates';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>'Abstracts by day',
    'type' =>'bar',
    'y_label' =>'abstracts',
    'series' =>false
    ];

$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME ."-$group", $Indico->data[$group][$id], 
    [ 'by_dates_show_zero' =>true, 'x_type' =>'date', 'x_upper_limit' =>strtotime($dates['abstracts_submission']['deadline']) +10*86400 ] );



$id ='by_days_to_deadline';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>'Abstracts progress',
    'type' =>'scatter',
    'x_label' =>sprintf( 'days to deadline (%s)', substr( $dates['abstracts_submission']['deadline'], 0, 10 )),
    'y_label' =>'abstracts',
    'series' =>false
    ];

$dtd_limit =-15;
$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME, $Indico->data[$group][$id], [ 'sum' =>true, 'x_low_limit' =>$dtd_limit, 'x_upper_limit' =>10 ] );
$vars['abstracts_n'] =number_format( $sum, 0, ',', '.' );


for ($year =22; $year >=19; $year --) {
    $charts[$chart_id]['series']['IPAC'.$year] =import_data( "abstracts-ipac${year}.txt", $dtd_limit );
}



// REGISTRANTS -------------------------------------------------------------------

$group ='registrants';
$id ='by_dates';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>'Registrants by day',
    'type' =>'bar',
    'y_label' =>'registrants',
    'series' =>false
    ];

$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME, $Indico->data[$group]['stats'][$id], [ 'by_dates_show_zero' =>true, 'x_type' =>'date' ] );


$id ='by_days_to_deadline';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>'Registrants progress',
    'type' =>'scatter',
    'y_label' =>'registrants',
    'x_label' =>'days to deadline',
    'series' =>false
    ];

$dtd_limit =-120;
$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME, $Indico->data[$group]['stats'][$id], [ 'sum' =>true, 'x_low_limit' =>$dtd_limit ] );
$vars['registrants_n'] =number_format( $sum, 0, ',', '.' );



$year =17;
$charts[$chart_id]['series']['IPAC'.$year] =import_data( "${group}-ipac${year}.txt" );


// COUNTRIES -------------------------------------------------------------------

$id ='country';
$chart_id ="${group}_${id}";
$charts[$chart_id] =[
    'title' =>'Affiliations by country',
    'type' =>'bar',
    'y_label' =>'registrants',
    'series' =>false
    ];

$charts[$chart_id]['series'][CONF_NAME] =get_chart_serie( CONF_NAME, $Indico->data[$group]['stats'][$id] );
$vars['country_n'] =count( $Indico->data[$group]['stats'][$id] );

$Indico->data[$group]['stats'][$id]['United States of America'] =$Indico->data[$group]['stats'][$id]['United States'];

$country_values =json_encode( $Indico->data[$group]['stats'][$id] );



// CHARTS -------------------------------------------------------------------

make_charts( $charts );


$vars['js'] .="

fetch('https://unpkg.com/world-atlas/countries-50m.json').then((r) => r.json()).then((data) => {
    const countries = ChartGeo.topojson.feature(data, data.objects.countries).features;

country_values =$country_values;

d ={
    labels: countries.map((d) => d.properties.name),
    datasets: [{
      label: 'Countries',
      data: countries.map((d) => ({feature: d, value: (d.properties.name in country_values ? country_values[d.properties.name] : 0)  })),
    }]
  };

const chart = new Chart(document.getElementById(\"registrants_country_code\").getContext(\"2d\"), {
  type: 'choropleth',
  data: d,
  options: {
    showOutline: true,
    showGraticule: true,
    plugins: {
      legend: {
        display: false
      },
    },
    scales: {
      xy: {
        projection: 'equalEarth'
      }
    }
  }
});
});
";

foreach([ 'papers', 'payments', 'country'] as $k) {
    if (!$vars[$k.'_n']) $vars['js'] .="$('#$k').hide();\n";
}

$T->set( $vars );

echo $T->get();


//-----------------------------------------------------------------------------
function make_charts( $_def ) {
    global $vars;

    $out =false;

    $cfg =[
        'colors' =>[ 
            '47,75,140', // blu
            '186,31,50',  // rosso
            '49,139,66', // verde
            '114,135,206', // azzurro
            '255,126,0', // arancio
            ]
        ];

    $color =$cfg['colors'][1];

    foreach ($_def as $canvas_id =>$chart) {
        $out .="
        var options ={
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12
                        }
                    },

                title: {
                    display: true,
                    color: 'black',
                    padding: {
                        bottom: 40
                        },
                    text: '$chart[title]',
                    font: {
                        size: '16px'
                        }
                    }
                },

                scales: {
                    xAxes: { 
                        title: {
                            text: '$chart[x_label]',
                            display: true
                            }
                        }            
                    }
        };";

        $datasets =[];
        $s =0;
        foreach ($chart['series'] as $sid =>$serie) {
            $color1 ="rgba( " .$cfg['colors'][$s] .", 1 )";
            $color2 ="rgba( " .$cfg['colors'][$s] .", 0.5 )";

            $datasets[$s] =[ 
//                'label' =>"$chart[y_label] $sid", 
                'label' =>$sid, 
                'lineTension' =>0.5,
                'borderWidth' =>2,
                'pointRadius' =>0,
                'borderColor' =>$color1,
                'backgroundColor' =>$color2,
                'data' =>$serie,
                'fill' =>true,
                'showLine' =>true 
                ];

            if ($chart['type'] == 'scatter') {
                $datasets[$s]['pointRadius'] =1;
                if ($s > 0) $datasets[$s]['backgroundColor'] ="rgba( " .$cfg['colors'][$s] .", 0 )";
            }

            $s ++;
        }
    
        $out .="
        var $canvas_id = new Chart( document.getElementById('$canvas_id'), {
            type: '$chart[type]',
            data: { datasets: " .json_encode( $datasets, true ) ."},
            options: options
            });
        ";
    }

    $vars['js'] .="
var _cfg ={
    'colors': [ 
        '27,148,50', // verde
        '35,111,176', // blu
        '100,100,100',  // rosso
        ]
    };

Chart.defaults.font.family =\"Open Sans\";
Chart.defaults.animation = false;

function draw() {
    $out
}
    ";
}


//-----------------------------------------------------------------------------
function import_payments() {
    global $cfg;

    $payments =file_read_json( '../../indico/status.json', true );
/* 
    $data =[
        'count' =>[ substr( $cfg['dates']['registration']['from'], 0, 10 ) =>0 ],
        'amount' =>[ substr( $cfg['dates']['registration']['from'], 0, 10 ) =>0 ]
        ];
 */
    foreach ($payments as $x) {
        if (!empty($x['ts']) && $x['amount'] > 0) {
            $ymd =date( 'Y-m-d', $x['ts'] );
    
            if (!isset($data['count'][$ymd])) {
                $data['count'][$ymd] =0;
                $data['amount'][$ymd] =0;
            }
        
            $data['count'][$ymd] +=1;
            $data['amount'][$ymd] +=$x['amount'];
        }
    }

    $ymd =date( 'Y-m-d' );
    if (!isset($data['count'][$ymd])) {
        $data['count'][$ymd] =0;
        $data['amount'][$ymd] =0;
    }

    $deadline_ts =strtotime($cfg['dates']['registration']['deadline']);

    $data2 =[];
    foreach ($data['count'] as $x =>$y) {
        $k =floor((strtotime($x) -$deadline_ts) /DAYS);
        $data2['count'][$k] =$y;
    }

    foreach ($data['amount'] as $x =>$y) {
        $k =floor((strtotime($x) -$deadline_ts) /DAYS);
        $data2['amount'][$k] =$y;
    }

    return [ 'by_dates' =>$data, 'by_days_to_deadline' =>$data2 ];
}


//-----------------------------------------------------------------------------
function import_data( $_fname, $_dtd_limit =-100 ) {
    $out =[];

    $t =file( $_fname );
    list( $from, $to ) =explode( ' > ', $t[1] );
    $from_ts =strtotime( $from );
    $to_ts =strtotime( $to ) +DAYS;

    for ($i =2; $i <count($t); $i ++) {
        if (substr( $t[$i], 0, 1 ) != '#') { 
            list( $y, $m, $d, $n ) =explode( ',', $t[$i] );
            $m ++;
            $ts =strtotime( "$y-$m-$d" );
            if ($ts < $from_ts) {
                $n0 =$n;
        
            } else if ($ts <=$to_ts) {
                $dtd =floor(($ts -$to_ts) /DAYS);
                if ($dtd >= $_dtd_limit) $out[] =[ 'x' =>$dtd, 'y' =>$n -$n0 ];    
            } else {
                
            }
        }
    }

    return $out;
}

//-----------------------------------------------------------------------------
function get_chart_serie( $_serie_name, $_data, $_cfg =[]) {
    global $sum;

    $serie =[];
    
    $ldate_ts =false;
    $sum =0;

    foreach ($_data as $x =>$y) {
        if (!empty($_cfg['by_dates_show_zero'])) {
            $date_ts =strtotime($x);
            if ($ldate_ts && ($date_ts -$ldate_ts >DAYS)) {
                for ($ts =$ldate_ts +DAYS; $ts < $date_ts && (empty($_cfg['x_upper_limit']) || $ts <= $_cfg['x_upper_limit']); $ts +=DAYS) {
                    $serie[] =[ 'x' =>date( 'y-m-d', $ts ), 'y' =>0 ];
                }
            }
        }
    
        $sum +=$y;

        $val_y =(empty($_cfg['sum']) ? $y : $sum);

        if ($_cfg['x_type'] == 'date') {
            $x_ts =strtotime($x);

            if ((empty($_cfg['x_low_limit']) || $x_ts >= $_cfg['x_low_limit'])
                && (empty($_cfg['x_upper_limit']) || $x_ts <= $_cfg['x_upper_limit'])) $serie[] =[ 'x' =>$x, 'y' =>$val_y ];
        
            if (!empty($_cfg['by_dates_show_zero'])) $ldate_ts =$date_ts;

        } else {
            if ((empty($_cfg['x_low_limit']) || $x >= $_cfg['x_low_limit'])
                && (empty($_cfg['x_upper_limit']) || $x <= $_cfg['x_upper_limit'])) $serie[] =[ 'x' =>$x, 'y' =>$val_y ];
        }

    }

    return $serie;
}

?>