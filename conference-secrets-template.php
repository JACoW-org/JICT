<?php
$cws_config['global']['indico_token']	    =''; //Get it from the indico.jacow.org page 
$cws_config['global']['indico_oauth'] =[
			'client_id'		=>"", // ask the Indico Team
			'client_secret' =>"", // ask the Indico Team
			'redirect_uri' 	=>$cws_config['global']['root_url']."/indico_oauth.php"  // https://www.ipacXX.org/JICT/indico_oauth.php
    ];
?>