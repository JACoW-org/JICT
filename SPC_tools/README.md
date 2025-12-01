***Directory SPC_tools:***

Set of tool to help the Scientific Program Committee.

**vote.php**
Page to allows the SPC top vote on the abstracts.
This page requires PHP to work properley.

PHP reads the list of abstracts (without cache) and extracts the vote for each of them.
Eacxh abstract is a row of a table with 2 buttons allowing to vote. When one of these buttons is clicked a javacsript function is calle dto allow to cast of vote. The vote is recorded by making a POST request to record_vote.php. Then a javacsript function is called to update the row.

Comments can also be posted by adding a comment, using javascript to send the comment by a POST request to the record_comment.php script.

The votes summary table and the counters in the navigation bar are populated by the javacsript function count_votes() it is called automatically after the page is loaded and after an abstract is refreshed.

Configuration: For this page to work, the question numbers for 1st choice and 2nd choice must be found in the source code of an abstract page. This could be automated.

Troubleshooting: in case of problems check at the top of the page, below the conference logo that the user name is correctly displayed. The line below the username will give debug information as well as the javascript console and the color of the row on which the action was performed.

Features to be added: 
- Option to work without javascript. 
- Add the possibility to do track changes.
- Autoconfig for the question numbers.
- Create a page summarizing the votes by SPC member.
- Create a page ranking the abstracts by votes in each MC.
