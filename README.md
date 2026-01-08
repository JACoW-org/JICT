# JICT - JACOW-Indico Conference Tools

**Usage:**
*setup conference-config.php:*
You must customise conference-config.php to the parameters of your conference.

*setup conference-secrets.php:*
You need to get from the JACoW IT coordinatore a oauth token for your application.
You need to download from https://indico.jacow.org/user/tokens/ an API Toekn.

*indico_stats_importer:*
Calculates statistics about the conference (change the .htaccess if you want to call it through http).

Configuration syntax:

cws_config['indico_stats_importer']['registrants_load_extra_data']=1; //read data from the registration form to extract statistics
$cws_config['indico_stats_importer']['registrants_extra']=[ 
    [ 'name' => 'Visa', 'type'=> "count", 'field' => "Do you need an invitation letter for visa" ], //count entries in the registration form for the field named "Do you need an invitation letter for visa"; "name" is the title that will be displayed on the chart.
    [ 'name' => 'Visits', 'type'=> "choice", 'field' => "Facility Tours" ], //count entries for the different choices in the registration form for the field named "Facility Tours"
    [ 'name' => 'Lunch boxes', 'type'=> "multiple", 'fields' => [ "Lunch box - Monday" , "Lunch box - Tuesday" , "Lunch box - Wednesday" , "Lunch box - Thursday" ,  "Lunch box - Friday" ]], //makes one chart with the entries from all the fieldds named in "fields"
];

$cws_config['page_dashboard']['order'] = [ 'abstracts', 'registrants', 'registrants_extra', 'delegates', 'paid_status', 'country', 'payments', 'papers' ];



*page_dashboard:*
Displays various statistics about the conference.
Statistics are calculated when calling indico_stats_importer/make.php.


*page_registration:*
Checks that data entered in the forms are not inconsistent with a ste of rules given.
Configuration syntax:

$cws_config['registrations'] =[
		'name'				=>'Registration checks',
		'allow_roles'		=>[ '*' ],
        'tmp_path'			=>'{tmp_path}/indico',
		'template'			=>'template.html',
		'default_page'		=>'page_registrations/check_registrations.php'
	];
$cws_config['registrations']["incompatibilities"]= //Check for incompatibilities (values of two fields of the registration form that are incompatible together)
[
[ "Lunch box - Friday" => "No" , "Visit to SOLEIL in Paris area" => "Yes" ],
[ " I like to reserve a bus from Roissy Charles de Gaulle (CDG) or Orly (ORY) to Deauville on Saturday 16th May" => "Yes" , " I like to reserve a bus from Roissy Charles de Gaulle (CDG) or Orly (ORY) to Deauville on Sunday 17th May"=> "Yes"],
[ "Visit to GANIL in Caen"=> "Yes", "Visit to SOLEIL in Paris area"=> "Yes"], 
[ "Visit to GANIL in Caen"=> "Yes", "Visit to ESRF in Grenoble area"=> "Yes"], 
[ " Visit to SOLEIL in Paris area"=> "Yes", "Visit to ESRF in Grenoble area"=> "Yes"], 
] ;

$cws_config['registrations']["dates_check"] = [ //Check for dates that are inconsistent in the registration form
[ "Arrival date" , "<22/5/2026"],
[ "Departure date" , ">17/5/2026"], 
[ "Arrival date" , "<Departure date"],
[ "Passport expiration date" , ">01/07/2026" ]
];
