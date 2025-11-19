/****
 *
 * SPC Voting tools
 *
 * January 2025 Created by Nicolas Delerue
 * 16.11.2025 Adapted to JICT 
 * 
 ****/

document.getElementById('info').innerText = "Loading... Javascript OK...";


//functions
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
};

var abstracts_ids = new Map();
abstract_id_column=0;
MC_column=2;
vote_column=7;
vote_mc_column=8;


var voteTable = document.getElementById("votes");
var cellsVote = voteTable.querySelectorAll("tr");

var the_document = document;
var dataTable = document.getElementById('abstracts_table');
var thecell = document.getElementById('TR-113');
var cells = dataTable.querySelectorAll("TR");    

//Load the abstracts
for (var irow = 1; irow < cells.length; irow++){
    abstract_id= cells[irow].getAttribute("id").substring(3).trim();
    abstracts_ids.set(abstract_id,irow);
} //for each abstract

//console.log(abstracts_ids);

function get_abstract_url(abtract_id){
    abtracts_base_url="https://indico.jacow.org/event/"+event_id+"/abstracts/"; 
    abstract_url=abtracts_base_url+abstract_id+"/";
    return abstract_url;
}//get_abstract_url

recreate_abstract_dict();

function recreate_abstract_dict(){
    abstracts_ids = new Map();
    //dataTable = document.getElementById("abstracts");
    //cells = dataTable.querySelectorAll("tr");
    for (var irow = 1; irow < cells.length; irow++){
        abstract_id= cells[irow].getAttribute("id").substring(3).trim();
        abstracts_ids.set(abstract_id,irow);
    } //for each abstract
} //recreate_abstract_dict

function color_abstract(abstract_id,thecolor){    
    //For colors see https://htmlcolorcodes.com/color-chart/
    var irow=abstracts_ids.get(""+abstract_id);
    for (icol=0;icol<cells[irow].cells.length;icol++){
         cells[irow].cells[icol].style.backgroundColor=thecolor;
    } //for each col
}//color_abstract


function update_abstract(abstract_id){
            console.log("update_abstract "+abstract_id);
            document.getElementById('info').innerText = "Loading abstract"+abstract_id;
            color_abstract(abstract_id, '#82E0AA')
            var abstract_http = new XMLHttpRequest();
            abstract_http.onreadystatechange = function() {
                var irow=abstracts_ids.get(""+abstract_id);
                if (this.readyState == 4){                    
                    document.getElementById('info').innerText = "Abstract "+abstract_id+" status "+this.status+" irow="+irow;
                    if (this.status == 200) {
                        //console.log("Abstract received:",abstract_id);
                        //console.log(JSON.parse(this.response)["abstracts"][0]["id"]);
                        review_found=false;
                        thereview={};
                        for (iloop=0; iloop<JSON.parse(this.response)["abstracts"][0]["reviews"].length; iloop++){
                            if ((JSON.parse(this.response)["abstracts"][0]["reviews"][iloop]["user"]["first_name"]==user_first_name)&&(JSON.parse(this.response)["abstracts"][0]["reviews"][iloop]["user"]["last_name"]==user_last_name)){
                                //console.log("found review for user");
                                thereview=JSON.parse(this.response)["abstracts"][0]["reviews"][iloop];
                                review_found=true;
                            }
                        }
                        if (review_found==true){
                            //console.log(thereview["ratings"]);
                            if (thereview["ratings"][0]["value"]==true){
                                current_vote="1";
                                current_vote_text="1st choice";
                                color_abstract(abstract_id, '#c39bd3');
                            } else if (thereview["ratings"][1]["value"]==true){
                                current_vote="2";
                                current_vote_text="2nd choice";
                                color_abstract(abstract_id, '#a9cce3');
                            } else {
                                current_vote="3";
                                current_vote_text="No vote";
                                color_abstract(abstract_id, '#ffffff');
                            }
                            cells[irow].cells[vote_column].innerHTML=current_vote_text;
                            for (iloop=1;iloop<=3;iloop++){
                                if (iloop==1){
                                    btn_text="1st choice";
                                } else if (iloop==2) {
                                    btn_text="2nd choice";
                                } else {
                                    btn_text="Cancel vote";
                                }
                                vote_btn="<form><input type=\"button\" onclick=\"vote("+iloop+","+abstract_id+","+thereview["id"]+","+thereview["track"]["id"]+")\" value=\"Vote "+btn_text+"\"></button></form>\n";
                                if (iloop!=parseInt(current_vote)){
                                    cells[irow].cells[vote_column].innerHTML+=vote_btn;
                                }
                            } //for iloop                            
                            cells[irow].cells[vote_mc_column].innerHTML=cells[irow].cells[MC_column].innerHTML+"_"+current_vote;
                        } //review found
                        else {
                            cells[irow].cells[vote_column].innerHTML="Error, please reload page\n";
                        }
                   }//status == 200
                   
                   else {
                       console.log("Abtract status:",this.status,this.responseURL) 
                       cells[irow].cells[vote_column].innerHTML="Error while loading: "+this.status+"</BR>"+"<form><button type=button onClick='update_abstract(\""+this.responseURL+"\")'>Reload</button></form>";
                   }
                
                   sleep(500).then(() => { count_votes(); });
               }//readyState
                document.getElementById('info').innerText = "";        
            }; //function
            abstract_http.timeout = function() {
                    console.log("timeout");
                    document.getElementById('info').innerText = "Timeout on "+this.responseURL;
            };
            abstract_url="get_abstract.php";
            post_data="abstract_id="+abstract_id;
            abstract_http.open("POST", abstract_url, true);
            abstract_http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
            abstract_http.send(post_data);            
}//function update_abstract

function count_votes(){
            var dataTable = document.getElementById("abstracts_table");
            var cells = dataTable.querySelectorAll("tr");
            //for each row
            firstVotes=new Array(8);
            secondVotes=new Array(8);
            for (var imc = 0; imc < 8; imc++){
                firstVotes[imc]=0;
                secondVotes[imc]=0;
            }
            for (var irow = 1; irow < cells.length; irow++){
                MCval=parseInt(cells[irow].cells[vote_mc_column].innerText.substring(2,3));
                vote_value=parseInt(cells[irow].cells[vote_mc_column].innerText.substring(4,5));
                if (irow<10){
                    console.log(cells[irow].cells[vote_mc_column].innerText);
                    console.log("MCval",MCval);
                    console.log("vote_value",vote_value);
                }
                if (vote_value==1){
                    firstVotes[MCval-1]+=1;
                }
                else if (vote_value==2){
                    secondVotes[MCval-1]+=1;
                }                
            } //for each row
            
            var first_choices_row=document.getElementById('votes').querySelectorAll("tr")[1];
            var second_choices_row=document.getElementById('votes').querySelectorAll("tr")[2];
            console.log(second_choices_row);
            for (var imc = 0; imc < 8; imc++){
                first_choices_row.cells[imc+1].innerText =firstVotes[imc];
                if (firstVotes[imc]<5){
                    first_choices_row.cells[imc+1].style.backgroundColor= '#F7DC6F'; //yellow
                } else if (firstVotes[imc]==5){
                    first_choices_row.cells[imc+1].style.backgroundColor= '#82E0AA'; //green
                } else {
                    first_choices_row.cells[imc+1].style.backgroundColor= '#F1948A'; //red
                }
                second_choices_row.cells[imc+1].innerText =secondVotes[imc];
                if (secondVotes[imc]<5){
                    second_choices_row.cells[imc+1].style.backgroundColor= '#F7DC6F'; //yellow
                } else if (secondVotes[imc]==5){
                    second_choices_row.cells[imc+1].style.backgroundColor= '#82E0AA'; //green
                } else {
                    second_choices_row.cells[imc+1].style.backgroundColor= '#F1948A'; //red
                }
            }
            for (var imc = 0; imc < 8; imc++){
                var navbar_mc=document.getElementById('MC'+(imc+1));
                navbar_mc.innerText="MC"+(imc+1)+": "+firstVotes[imc]+"+"+secondVotes[imc]+" = "+(firstVotes[imc]+secondVotes[imc]);
                if (firstVotes[imc]+secondVotes[imc]<10){
                    navbar_mc.style.backgroundColor= '#F7DC6F'; //yellow
                } else if ((firstVotes[imc]+secondVotes[imc]==10)&&(firstVotes[imc]==5)&&(secondVotes[imc]==5)){
                    navbar_mc.style.backgroundColor= '#82E0AA'; //green
                } else {
                    navbar_mc.style.backgroundColor= '#F1948A'; //red
                }
                navbar_mc.style.color= '#000000'; //black
            }
}//count_votes


function vote(vote_value,abstract_id,review_id,track_id){
    //console.log("voting");
    console.log("voting",abstract_id);
    color_abstract(abstract_id, '#F7DC6F');
    var vote_request = new XMLHttpRequest();
    vote_request.onreadystatechange = function() {
        if (this.readyState == 4){
            //console.log("vote_request");
            //console.log(this.status);
            //document.getElementById('info').innerText = "Status "+this.status;
            if (this.status == 200) {
               //console.log("vote_request 200");
               //console.log(this.responseText);
               document.getElementById('info').innerText = "Vote recorded";               
               //console.log("update",abstract_id);
               update_abstract(abstract_id);
           }//status
           else {
               console.log("vote_request error",this.status);
               document.getElementById('info').innerText = "Error recording vote: "+this.status;
           }
        }//readyState
    };
    query_url="record_vote.php";
    post_data="abstract_id="+abstract_id+"&review_id="+review_id+"&track_id="+track_id+"&vote_value="+vote_value;
    vote_request.open("POST", query_url, true);
    vote_request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    vote_request.send(post_data);
    //console.log("voted");
    document.getElementById('info').innerText = "Vote on abstract "+abstract_id+" recorded.";
    update_abstract(abstract_id);
} //vote

document.getElementById('info').innerText = "Loaded. Javascript OK.";
