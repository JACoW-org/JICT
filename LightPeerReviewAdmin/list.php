<?php

/* by Stefano.Deiuri@Elettra.Eu

2024.05.17 - update
2023.05.11 - update
2022.08.25 - filter function
2022.08.22 - refresh function
2022.08.20 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'test' );
$cfg['verbose'] =1;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

echo("auth OK ");
//echo var_dump($user);
echo "<BR/>here<BR/>"; 

$Indico->load();

echo("indico loaded<BR/>");

$req =$Indico->request( "/event/{id}/manage/abstracts/abstracts.json", 'GET', false, array( 'return_data' =>true, 'quiet' =>true ) );

foreach ($req["abstracts"] as $abs) {
    echo "<A HREF='http://indico.jacow.org/event/".$cfg['indico_event_id']."/abstracts/".$abs["id"]."'>".$abs["id"]."</A>:&nbsp;".$abs["friendly_id"].":&nbsp;".$abs["title"]."<BR/>";
}




/*
def comment_paper(paper_db_id, comment):
    print("Commenting paper ", paper_db_id)
    headers = {'Authorization': f'Bearer {api_token}'}
    payload = { 'comment': comment}
    data = requests.post(f'{event_url}papers/api/'+str(paper_db_id)+'/comment', headers=headers, data=payload)

    print(data)
    print(data.status_code)
    #if not (data.status_code == 200):
    #    exit()

*/

?>