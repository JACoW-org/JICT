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
var voteTable = document.getElementById("votes");
var cellsVote = voteTable.querySelectorAll("tr");

var the_document = document;
var dataTable = document.getElementById('abstracts_table');
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
    //document.getElementById('info').innerText += "Coloring "+abstract_id;
    var irow=abstracts_ids.get(""+abstract_id); 
    //console.log(cells[irow]);
    //console.log("colrow");
    cells[irow].style.backgroundColor=thecolor; 
    for (icol=0;icol<cells[irow].cells.length;icol++){
         cells[irow].cells[icol].style.backgroundColor=thecolor;
    } //for each col
    //document.getElementById('info').innerText += "; Done.";
}//color_abstract


function update_abstract(abstract_id){
            console.log("update_abstract "+abstract_id);
            document.getElementById('info').innerText += "Loading abstract"+abstract_id;
            color_abstract(abstract_id, '#82E0AA')
            var abstract_http = new XMLHttpRequest();
            abstract_http.onreadystatechange = function() {
                var irow=abstracts_ids.get(""+abstract_id);
                if (this.readyState == 4){                    
                    //document.getElementById('info').innerText += " status "+this.status+";";
                    if (this.status == 200) {
                        //console.log("Abstract received:",abstract_id);
                        //console.log(JSON.parse(this.response)["abstracts"][0]["id"]);
                        //console.log(JSON.parse(this.response)["abstracts"][0]["comments"]);                        
                        //console.log(JSON.parse(this.response)["abstracts"][0]["comments"].length);     
                        if (JSON.parse(this.response)["abstracts"][0]["comments"].length>0){
                            cells[irow].cells[col_comments].innerHTML="";
                            for (iloop=0; iloop<JSON.parse(this.response)["abstracts"][0]["comments"].length; iloop++){
                                cells[irow].cells[col_comments].innerHTML+=JSON.parse(this.response)["abstracts"][0]["comments"][iloop]["user"]["first_name"]+" ";
                                cells[irow].cells[col_comments].innerHTML+=JSON.parse(this.response)["abstracts"][0]["comments"][iloop]["user"]["last_name"]+":";
                                cells[irow].cells[col_comments].innerHTML+=JSON.parse(this.response)["abstracts"][0]["comments"][iloop]["text"]+"<BR/>\n";
                            }
                            cells[irow].cells[col_comments].innerHTML+="<BR/><form>\n";
                            cells[irow].cells[col_comments].innerHTML+="<INPUT type='hidden' name='abstract_id' value='"+abstract_id+"'>\n";
                            cells[irow].cells[col_comments].innerHTML+="<INPUT type='text' name='comment_"+abstract_id+"' id='comment_"+abstract_id+"' size='10'>\n";
                            cells[irow].cells[col_comments].innerHTML+="<INPUT type=button value='Add comment' onclick=\"add_comment("+abstract_id+")\">\n";
                            cells[irow].cells[col_comments].innerHTML+="</form>";
                        }
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
                            document.getElementById('info').innerText += "Got review;";
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
                            document.getElementById('abstract_'+abstract_id+'_review_id').value=thereview["id"];
                            cells[irow].cells[vote_column].innerHTML=current_vote_text;
                            new_track_id=0;
                            if (thereview["proposed_action"]=="change_tracks"){
                                new_track_id=thereview["proposed_tracks"][0]["id"];
                                new_track_code=thereview["proposed_tracks"][0]["code"];
                            }
                            for (iloop=1;iloop<=3;iloop++){
                                if (iloop==1){
                                    btn_text="1st choice";
                                } else if (iloop==2) {
                                    btn_text="2nd choice";
                                } else {
                                    btn_text="Cancel vote";
                                }
                                vote_btn="<form><input type=\"button\" onclick=\"vote("+iloop+","+abstract_id+","+thereview["id"]+","+thereview["track"]["id"]+","+new_track_id+")\" value=\"Vote "+btn_text+"\"></button></form>\n";
                                if (iloop!=parseInt(current_vote)){
                                    cells[irow].cells[vote_column].innerHTML+=vote_btn;
                                }
                            } //for iloop                            
                            cells[irow].cells[vote_mc_column].innerHTML=cells[irow].cells[MC_column].innerHTML.substring(0,3)+"_"+current_vote;
                            if (thereview["proposed_action"]=="change_tracks"){
                                cells[irow].cells[vote_column].innerHTML+="\n<BR/>Track change to "+new_track_code+" proposed\n";
                                cells[irow].cells[MC_column].innerHTML+="\n<BR/>Track change to "+new_track_code+" proposed\n";
                            }
                            //document.getElementById('info').innerText += "Done updating abstract;";
                        } //review found
                        else {
                            if (JSON.parse(this.response)["abstracts"][0]["comments"].length==0){
                                cells[irow].cells[vote_column].innerHTML="Error: review not found in the abstract. Please reload page\n";
                            }
                        }
                   }//status == 200
                   
                   else {
                       console.log("Abtract status:",this.status,this.responseURL) 
                       cells[irow].cells[vote_column].innerHTML="Error while loading: "+this.status+"</BR>"+"<form><button type=button onClick='update_abstract(\""+this.responseURL+"\")'>Reload</button></form>";
                   }
                
                   sleep(500).then(() => { count_votes(); });
               }//readyState
               // document.getElementById('info').innerText = "";        
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
            document.getElementById('info').innerText += "Counting votes";
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
                /*
                if (irow<10){
                    console.log(cells[irow].cells[vote_mc_column].innerText);
                    console.log("MCval",MCval);
                    console.log("vote_value",vote_value);
                }
                */
                if (vote_value==1){
                    firstVotes[MCval-1]+=1;
                }
                else if (vote_value==2){
                    secondVotes[MCval-1]+=1;
                }                
            } //for each row
            
            var first_choices_row=document.getElementById('votes').querySelectorAll("tr")[1];
            var second_choices_row=document.getElementById('votes').querySelectorAll("tr")[2];
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
    //document.getElementById('info').innerText += "Counted;";
}//count_votes


function vote(vote_value,abstract_id,review_id,track_id,new_track_id){
    //console.log("voting");
    console.log("voting",abstract_id);
    document.getElementById('info').innerText = "Voting on abstract "+abstract_id+";";
    color_abstract(abstract_id, '#F7DC6F');
    var vote_request = new XMLHttpRequest();
    vote_request.onreadystatechange = function() {
        if (this.readyState == 4){
            //console.log("vote_request");
            //console.log(this.status);
            //document.getElementById('info').innerText = "Status "+this.status;
            if (this.status == 200) {
               console.log("vote_request 200");
               document.getElementById('info').innerText += "Vote sent";
               if (this.responseText.includes("bool(false)")){     
                    //document.getElementById('info').innerText += "; Error recording vote";
                    console.log("false received"); 
                    console.log(this.responseText); 
               } else if (this.responseText.includes("bool(true)")){ 
                    document.getElementById('info').innerText += " OK;";
               } else {
                    document.getElementById('info').innerText += "; Unexpected response: "+this.responseText;
                    console.log("No bool received"); 
                    console.log(this.responseText); 
               }
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
    post_data="abstract_id="+abstract_id+"&review_id="+review_id+"&track_id="+track_id+"&vote_value="+vote_value+"+&new_track_id="+new_track_id;
    vote_request.open("POST", query_url, true);
    vote_request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    vote_request.send(post_data);
    //console.log("voted");
    //document.getElementById('info').innerText += "Vote on "+abstract_id+" recorded.";
    //update_abstract(abstract_id);
    //document.getElementById('info').innerText += "Voting done";
} //vote

function add_comment(abstract_id){
    console.log("commenting",abstract_id);
    document.getElementById('info').innerText = "Commenting on abstract "+abstract_id+";";
    comment=document.getElementById('comment_'+abstract_id).value;
    color_abstract(abstract_id, '#F7F7F7');
    var comment_request = new XMLHttpRequest();
    comment_request.onreadystatechange = function() {
        if (this.readyState == 4){
            //console.log("vote_request");
            //console.log(this.status);
            //document.getElementById('info').innerText = "Status "+this.status;
            if (this.status == 200) {
               document.getElementById('info').innerText += "Comment sent";
               //console.log("update",abstract_id);
               update_abstract(abstract_id);
           }//status
           else {
               console.log("comment_request error",this.status);
               document.getElementById('info').innerText = "Error recording comment: "+this.status;
           }
        }//readyState
    };
    query_url="record_comment.php";
    post_data="abstract_id="+abstract_id+"&comment="+comment;
    comment_request.open("POST", query_url, true);
    comment_request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
    comment_request.send(post_data);
    //document.getElementById('info').innerText += "Comment done";
} //add_comment

function change_track(abstract_id){
    document.getElementById('info').innerText = "Changing track on "+abstract_id+";";
    console.log("change track");
    //console.log(document.getElementById('change_track_'+abstract_id).value);
    var irow=abstracts_ids.get(""+abstract_id); 
    vote_value=parseInt(cells[irow].cells[vote_mc_column].innerText.substring(4,5));
    new_track_id=document.getElementById('change_track_'+abstract_id).value;
    review_id=document.getElementById('abstract_'+abstract_id+'_review_id').value;
    //console.log("review_id "+review_id);
    track_id=document.getElementById('abstract_'+abstract_id+'_track_id').value;
    //console.log("track_id "+track_id);
    vote(vote_value,abstract_id,review_id,track_id,new_track_id);
} //change_track

function show_hide_column(col_no, do_show) {
    console.log("show/hide");
    var tbl = document.getElementById('abstracts_table');
    var rows = tbl.getElementsByTagName('tr');
    //document.getElementById('info').innerText += "Hide/show "+col_no+" "+do_show+";";


    for (var row = 0; row < rows.length; row++) {
        var cols = rows[row].children;
        if (col_no >= 0 && col_no < cols.length) {
            var cell = cols[col_no];
            
            if ((cell.tagName == 'TH')||(cell.tagName == 'TD')) {
                cell.style.display = do_show ? 'block' : 'none';
                cell.style.width =  col_content_width;
                cell.style.height =  cols[0].clientHeight;
                cell.height =  cols[0].clientHeight;
                cell.style.height =  cols[0].clientHeight+" px";
                //cell.style.height =  "";
                //cell.draw();
            } 
            //if ((cell.tagName == 'TH')||(cell.tagName == 'TD')) cell.visibility = do_show ? 'hidden' : 'visible';            
        }
    }
    if (col_no==col_abstracts){
        if (do_show){
            document.getElementById('btnHideAbs').style.visibility = 'visible';
            document.getElementById('btnShowAbs').style.visibility = 'hidden';
        } else {
            document.getElementById('btnHideAbs').style.visibility = 'hidden';
            document.getElementById('btnShowAbs').style.visibility = 'visible';
        }
    } else if (col_no==col_comments){
        if (do_show){
            document.getElementById('btnHideComments').style.visibility = 'visible';
            document.getElementById('btnShowComments').style.visibility = 'hidden';
        } else {
            document.getElementById('btnHideComments').style.visibility = 'hidden';
            document.getElementById('btnShowComments').style.visibility = 'visible';
        }
    } 
    /*
    //Refresh the full table
    if (do_show){
        sleep(5000).then(() => { 
            console.log("hide table");
            document.getElementById('abstracts_table').style.display = do_show ? 'none': 'block'; 
            sleep(5000).then(() => { 
                console.log("show table");
                document.getElementById('abstracts_table').style.display = do_show ? 'block' : 'none'; 
            });
        });
    }
    */
} // show_hide_column

const btnHideAbs = document.getElementById( 'btnHideAbs' )
btnHideAbs.addEventListener( "click", () => show_hide_column( col_abstracts, false ))

const btnShowAbs = document.getElementById( 'btnShowAbs' )
btnShowAbs.addEventListener( "click", () => show_hide_column( col_abstracts, true ))

const btnHideComments = document.getElementById( 'btnHideComments' )
btnHideComments.addEventListener( "click", () => show_hide_column( col_comments, false ))

const btnShowComments = document.getElementById( 'btnShowComments' )
btnShowComments.addEventListener( "click", () => show_hide_column( col_comments, true ))

document.getElementById('btnShowAbs').style.visibility = 'hidden';
document.getElementById('btnShowComments').style.visibility = 'hidden';

document.getElementById('info').innerText = "Javascript OK.";
document.getElementById('info').innerText += ".";
sleep(1000).then(() => { count_votes(); });
sleep(1500).then(() => { document.getElementById('info').innerText += "."; });
console.log("Page loaded");
