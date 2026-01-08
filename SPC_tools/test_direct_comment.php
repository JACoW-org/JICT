<?php

/* by Nicolas.Delerue@ijclab.in2p3.fr

2025.11.20 - 1st version

*/

require( '../config.php' );
require_lib( 'jict', '1.0' );
require_lib( 'indico', '1.0' );

$cfg =config( 'SPC_tools' );
$cfg['verbose'] =1;

$Indico =new INDICO( $cfg );

$user =$Indico->auth();
if (!$user) exit;

echo("auth OK ");
//echo var_dump($user);
echo "<BR/>here<BR/>"; 

$Indico->load();

echo("indico loaded<BR/>");

//$req =$Indico->request( "/event/{id}/manage/abstracts/abstracts.json", 'GET', false, array( 'return_data' =>true, 'quiet' =>true ) );

/*
foreach ($req["abstracts"] as $abs) {
    echo "<A HREF='http://indico.jacow.org/event/".$cfg['indico_event_id']."/abstracts/".$abs["id"]."'>".$abs["id"]."</A>:&nbsp;".$abs["friendly_id"].":&nbsp;".$abs["title"]."<BR/>";
}
*/

if (count($_GET)>0){
    if ($code_testing==1) {
        echo "Using GET data\n";   
    }
    $abstract_id= $_GET['abstract_id'];
    $comment= $_GET['comment'];
} else {
    $abstract_id=109;
     $comment= "test1";
}

echo "<BR/> Get abstract page<BR/>\n";
$url = "https://indico.jacow.org/event/37/abstracts/".$abstract_id;

// use key 'http' even if you send the request to https://...
$options = [
    'http' => [
        'method' => 'GET',
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
var_dump(http_get_last_response_headers());
var_dump($result);

echo "<BR/> direct call<BR/>\n";
$url = "https://indico.jacow.org/event/37/abstracts/".$abstract_id."/comment";
$data = ['text' => 'fgc: '.$comment, 'visibility' => 'reviewers' , 'csrf_token' => '77c259b8-77c1-4b55-b0cd-078a076c2238'];

// use key 'http' even if you send the request to https://...
$options = [
    'http' => [
        'method' => 'POST',
         'header' => "Content-Type: application/json\r\n",
        'content' => json_encode($data),
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
var_dump(http_get_last_response_headers());
var_dump($data);
var_dump($result);


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