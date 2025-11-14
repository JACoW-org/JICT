# JICT - JACOW-Indico Conference Tools

**Usage:**
*setup conference-config.php:*
You must customise conference-config.php to the parameters of your conference.

*setup conference-secrets.php:*
You need to get from the JACoW IT coordinatore a oauth token for your application.
You need to download from https://indico.jacow.org/user/tokens/ an API Toekn.

*indico_stats_importer:*
Calculates statistics about the conference.

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
Statistics are calculated when calling indico_stats_importer/make.php (change the .htaccess if you want to call it through http).

