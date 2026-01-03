<?php

/* Created by Nicolas.Delerue@ijclab.in2p3.fr
2026.01.02 1st version

This page is an add-on that calls myvotes.php but allows to vote by typing the number of an abstract.

*/

//Create the form to vote
$vote_form="";
$vote_form.="<P><center>\n";
$vote_form.="<h3>Fill your votes here</h3>\n";
$vote_form.="</center>\n";
$vote_form.="<form action='manual_submission.php' method=\"post\">\n";
for ($iloop=1;$iloop<10;$iloop++){
    $vote_form.= "<BR/>Abstract friendly ID (without the #): <input type=\"number\" name=\"".$iloop."_abstract_id\" maxlength=\"6\" >\n";
    $vote_form.= "<INPUT type=\"radio\" name=\"".$iloop."_choice\" value=\"1\">1st choice\n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
    $vote_form.= "<INPUT type=\"radio\" name=\"".$iloop."_choice\" value=\"2\">2nd choice\n";
}
$vote_form.= "<BR/><INPUT type=\"submit\" name=\"submit\" value=\"Submit your votes\">\n";
$vote_form.="</form>\n";
$vote_form.="</P>\n";

require('myvotes.php');

?>
