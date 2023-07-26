======================================
CWS - JACOW Conference Website Scripts
======================================

.. contents::
	:local:

The JACoW Conference Website Scripts (written by Stefano Deiuri) is a set of PHP scripts that extracts data from SPMS and generates a series of HTML and JavaScript files as an output. 

Structure of the Scripts
------------------------

| jacow-spms-cws
| \|- app_paper_status/ - Directory with files for "Paper Status" web app
| \|- app_poster_police/ - Directory with files for "Poster Police" web app
| \|- barcode/ - Directory with files for barcode management at the PO
| \|- make_chart_abstracts/ - Directory with files for generating the "Abstracts" chart
| \|- make_chart_papers/ - Directory with files for generating the "Papers" chart
| \|- make_chart_registrants/ - Directory with files for generating the "Registrants" chart
| \|- make_page_participants/ - Directory with files for generating the "Participants" page
| \|- make_page_programme/ - Directory with files for generating the "Scientific Program" page
| \|- page_po_status/ - Directory with files for "Proceedings Status" page
| \|- spms_importer/ - Directory with files for import data form the SPMS
| \|- data/ - Temporary directory for e.g. data downloaded from SPMS
| \|- html/ - This directory contains by default all the output files
| \|- libs/ - Common .php files required by all the scripts
| \|- tmp/ - Temporary directory
| \|- config.php - script configuration information (like SPMS location and passphrases, output directory)
| \|- cron.php - script to add in Cron Jobs (in the config.php you can select the scripts to run and when)
| \|- index.php - An index file with URLs to all content generated into the "html" directory
| \|- README - This readme

How to use the Scripts
----------------------

Each of the directories for generating a page or a chart contains a file "make.php". This file is NOT INTENDED for executing it through a webserver, it is enough to have php-cli and execute it from time to time using e.g. Cron Jobs. Depending on the type of scripts the time between executions can vary. I suggest to updated the abstracts chart every 60 minutes, other Charts like the scientific program should be generated once per day.

Example how to execute the script from the command line to generate an "Abstracts" chart:

| $ cd make_chart_abstracts/
| $ php make.php 
| Get data from: oraweb.cern.ch/pls/ipac2014/xtract.abstractsubmissions.. OK (38 records)
| Save file ../html/ChartAbstracts.html... OK
| Save file ../html/ChartAbstracts.js... OK
| $ _

After that the file "../html/ChartAbstracts.html" can be embedded into the conference website. Example using an iframe:

| <iframe src ="html/ChartAbstracts.html" scrolling="no" width="550px" height="220px" frameborder="0" name="abstracts_chart" id="abstracts_chart">
|   <p>Your Browser does not support embedded Frames (iframes).
|     You can access the page here: <a href="html/CharsAbstracts.html">Abstracts Submitted</a>
|   </p>
| </iframe>


The config.php file contains several options. One can change the output directory ("OUT_PATH") and the temporary directory ("TMP_PATH") of the scripts to accommodate the structure of the conferences webserver. Also the charts width and height ("CHART_WIDTH", "CHART_HEIGHT") can be changed to fit the conference website. 
Also the URL to the SPMS instance to extract the data and a passphrase (the same entered in SPMS) need to be placed in the config.php file, as well as the name of the confernce.


