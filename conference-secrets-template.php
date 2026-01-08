<?php
$cws_config['global']['indico_token']	    =''; //Get it from the indico.jacow.org page: https://indico.jacow.org/user/tokens/
$cws_config['global']['indico_oauth'] =[ // ask the JACoW contact for Indico
			'client_id'		=>"", 
			'client_secret' =>"", 
			'redirect_uri' 	=>$cws_config['global']['root_url']."/indico_oauth.php"  // https://www.ipacXX.org/JICT/indico_oauth.php
    ];
?>