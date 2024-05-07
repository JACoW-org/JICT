<?php

/* by Stefano.Deiuri@Elettra.Eu

2023.11.27 - handle public access mode
2023.03.01 - fix session block handlers
2022.08.29 - remove edots
2022.08.18 - save_citations
2022.07.13 - 1st version

*/

/* 

The Indico Editorial Module for JACoW
https://codimd.web.cern.ch/h6pGyyyqQK-g7uU9Gr7q7Q

JACoW Workflow using Indico
https://codimd.web.cern.ch/s/d2XPNF5L9#Editing-states

*/

define( 'FAIL_QA_STRING', 'his revision has failed QA.' );

define( 'MAP_STATUS', [ 'accepted' =>'g', 'acceptance' =>'g', 'needs_submitter_confirmation' =>'y', 'needs_submitter_changes' =>'r', 
'assigned' =>'a', 'nofiles' =>'nofiles', 'ready_for_review' =>'files', 'rejected' =>'x' ]);

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class INDICO extends JICT_OBJ {
	var $source_file_type_id =false;
	var $editing_tags =false;

	//-------------------------------------------------------------------------
	function __construct( $_cfg =false, $_load =false ) {
		$this->api =new API_REQUEST( $_cfg['indico_server_url'] );
		$this->api->config( 'authorization_header', 'Bearer ' .$_cfg['indico_token'] );

		$this->event_id =$_cfg['indico_event_id'];
		
		if ($_cfg) $this->config( $_cfg );

		if (empty($this->cfg['cache_time'])) $this->cfg['cache_time'] =60;

        if ($_load) $this->load();

		$this->debug =false;
	}

	//-----------------------------------------------------------------------------
	function auth() {
		session_start();

		if (empty($this->cfg['indico_oauth']) || empty($this->cfg['indico_oauth']['client_id'])) {
			$user =[
				'full_name' =>'public',
				'email' =>'public',
				'public' =>true
				];

			return $user;
		}

		$login_message ="To use the utility please login with your Indico account<br /><br /><a href='$_SERVER[PHP_SELF]?cmd=login'>Log In</a>";

		if (!empty($_GET['cmd'])) {
			if ($_GET['cmd'] == 'logout') {
				$_SESSION['indico_oauth'] =false;
				echo $login_message;
				exit;
			}
	
			if ($_GET['cmd'] == 'login') {
				$this->oauth( 'authorize' );
				exit;
			}
		}

		if (empty($_SESSION['indico_oauth']['token']) || strlen($_SESSION['indico_oauth']['token']) < 20) {
			echo $login_message;
			exit;
		}

		if (!empty($this->cfg['allow_roles'])) {
			if ($this->cfg['allow_roles'][0] == '*' && empty($_SESSION['indico_oauth']['user']['roles'])) {
				echo "You don't have an Indico role!";
				exit;			
			}

			if ($this->cfg['allow_roles'][0] != '*' && empty(array_intersect( $this->cfg['allow_roles'], $_SESSION['indico_oauth']['user']['roles'] ))) {
				echo "Your Indico roles don't include " .implode( ' or ', $this->cfg['allow_roles'] );
				exit;
			} 
		}

		return $_SESSION['indico_oauth']['user'];
	}

	//-----------------------------------------------------------------------------
	function oauth( $_request, $_message =false ) {
//		global $indico_api, $cws_config;

		$oauth_cfg =$this->cfg['indico_oauth'];

		switch ($_request) {
			case 'token':
	//            use_class( 'api_request', '1.1' );
//				$indico_api =new API_REQUEST( 'https://indico.jacow.org' );
//				$indico_api->configs([ 'ignore_errors' =>'true' ]);

				$rqst_data =[
					'client_id'		=>$oauth_cfg['client_id'],
					'client_secret'	=>$oauth_cfg['client_secret'],
					'code'			=>$_GET['code'],
					'grant_type'	=>'authorization_code',
					'redirect_uri'  =>$oauth_cfg['redirect_uri']
					];

				$user_token =$this->request( '/oauth/token', 'POST', $rqst_data, [ 'disable_cache' =>true, 'return_data' =>true ]);

				$_SESSION['indico_oauth']['error'] =false;
				$_SESSION['indico_oauth']['token'] =$user_token['access_token'];
				
				$this->api->config( 'authorization_header', 'Bearer ' .$user_token['access_token'] );

				$user =$this->request( '/api/user', 'GET', false, [ 'disable_cache' =>true, 'return_data' =>true ]);
				$user['full_name'] ="$user[first_name] $user[last_name]";
				$_SESSION['indico_oauth']['user'] =$user;

				$this->api->config( 'authorization_header', 'Bearer ' .$this->cfg['indico_token'] );

				$roles =$this->request( '/event/{id}/manage/roles/api/roles/', 'GET', false, [ 'return_data' =>true ]);
		
				$user_roles =[];
				foreach ($roles as $role) {
					foreach ($role['members'] as $member) {
						if ($member['id'] == $user['id']) {
							$user_roles[] =$role['code'];
							if ($role['code'] == 'WSA') $_SESSION['indico_oauth']['user']['admin'] =true;
						}
					}
				}

				$_SESSION['indico_oauth']['user']['roles'] =$user_roles;
				break;  

			case 'authorize':
				$authorize_url =$this->cfg['indico_server_url'] .'/oauth/authorize?'
					.http_build_query([
						'client_id'     =>$oauth_cfg['client_id'],
						'redirect_uri'  =>$oauth_cfg['redirect_uri'],
						'response_type'	=>'code',
						'scope'         =>'read:user read:everything full:everything read:legacy_api'
						]);
			
				header( 'Location: ' .$authorize_url );
				exit;

			case 'error':
				$_SESSION['indico_oauth']['error'] =[ 'error' =>true, 'message' =>$_message ];
				break;  
		}
	}


	//-------------------------------------------------------------------------
	function request( $_request, $_method ='GET', $_data_request =false, $_rqst_cfg =false ) {
		$req =str_replace( "{id}", "$this->event_id", $_request );

        $fname =trim(str_replace( '/', '_', $req ), '_');
        if (substr( $fname, -5 ) != '.json') $fname .='.json';

		$verbose =empty($_rqst_cfg['quiet']);

		if ($_method != 'GET') $_rqst_cfg['disable_cache'] =true;

		//print_r( $_rqst_cfg );

		if (isset($_rqst_cfg['cache_time'])) $cache_time =$_rqst_cfg['cache_time'];
        else $cache_time =empty($_rqst_cfg['disable_cache']) ? $this->cfg['cache_time'] : 0;

		$cache =new CACHEDATA( $fname, $cache_time, APP_TMP_PATH );

		if (!$cache->get( $this->data[$req] )) {
			$t0 =time();
			if ($verbose) $this->verbose( "# $_method ($cache_time) $req... ", 2 );
			
			$this->data[$req] =$this->api->request( $req, $_method, $_data_request );
			
			if ($verbose) {
				$this->verbose_status( empty($this->data[$req]), "NO_DATA" );
//				$this->verbose_status( $this->data[$req], sprintf( "(%s) ", (time() -$t0) ));
			}

            if (empty($_rqst_cfg['disable_cache'])) {
                if ($verbose) $this->verbose( "# SAVE " .APP_TMP_PATH ."/$fname... ", 2 );
				$cache_status =$cache->save( $this->data[$req] );
                if ($verbose) $this->verbose_status( !$cache_status );
            }

		} else {
			if ($verbose) $this->verbose( "# USE CACHE $fname... OK", 2 );
		}

        if (empty($_rqst_cfg['return_data'])) return $req;

        return $this->data[$req];
	}

	//-------------------------------------------------------------------------
	function get_pdf_url( $_paper_id ) {
		$x =$this->request( "/event/{id}/api/contributions/$_paper_id/editing/paper", 'GET', false, [ 'return_data' =>true, 'quiet' =>true ]);

		$pcode =$x['contribution']['code'];

//		print_r( $last_revision );exit;

		$url =false;
		foreach ($x['revisions'] as $revision) {
			if (empty($revision['is_undone']) && !empty($revision['files'])) {
				foreach ($revision['files'] as $f) {
					if ($f['filename'] == "$pcode.pdf") $url =$f['external_download_url'];
				}
			}
		}

		$this->data['papers'][$pcode]['editor'] =$x['editor']['full_name'];

		return $url;
	}

	function paper_status() {

	}

	//-------------------------------------------------------------------------
	function import_stats() {
		/*         
        $now =time();

		if (strtotime($this->cfg['dates']['abstracts_submission']['from']) <= $now
            && strtotime($this->cfg['dates']['abstracts_submission']['to']) >= $now
            ) {
                $this->import_abstracts_list();
            } else {
				unset($this->cfg['out_abstracts_stats']);
			} 
			
		if (strtotime($this->cfg['dates']['registration']['from']) <= $now
            && strtotime($this->cfg['dates']['registration']['to']) >= $now
            ) {
				$this->import_registrants();
            } else {
				unset($this->cfg['out_registrants']);
			}
		*/

		//$this->import_registrants();

		$this->verbose( "\nProcess stats" );

		$papers_list =$this->request( '/event/{id}/editing/api/paper/list', 'GET', false, 
			[ 'return_data' =>true, 'disable_cache' =>true ] );

		$now =time();

		$map_status =MAP_STATUS;

		$nums =[ 'qaok' =>0, 'files' =>0, 'a' =>0, 'g' =>0, 'y' =>0, 'r' =>0, 'nofiles' =>0, 'processed' =>0, 'total' =>0 ];
		$editor_stats_init =[ 'g' =>0, 'y' =>0, 'r' =>0, 'a' =>0, 'revisions' =>0, 'qa_fail' =>0, 'qa_ok' =>0 ];
		
		$editors =[];
		$editor_papers =[];
		$editor_papers_list =[];
		$editor_stats =[];
		$days =[ 'processed' =>[] ];

		$revisions =$this->data['revisions'];

/* 		$pedit_options =[ 'return_data' =>true, 'quiet' =>false, 'cache_time' =>86400*30 +3600 ];
		if ($this->cfg['cache_time'] == 0) $pedit_options['cache_time'] =0; */

		foreach ($papers_list as $x) {
			$pcode =$x['code'];

            if (!empty($pcode) && !empty($this->data['papers'][$pcode])) {
				$p =$this->data['papers'][$pcode];

				$paper_status =empty($x['editable']) ? 'nofiles' : $x['editable']['state'];
				
				$editor =false; // first editor
				$reditor =false; // revision editor

				$istatus =false;  // indico status

				if (!empty($x['editable']['editor'])) {
					$rev_id =$this->get_rev_id( $x['editable'] );

					if (empty($revisions[$pcode]) || $rev_id != $revisions[$pcode]) {
						echo sprintf( "\nUPDATE %s %s > %s\n", $pcode, (empty($revisions[$pcode]) ? "NEW" : $revisions[$pcode]), $rev_id );
						$revisions[$pcode] =$rev_id;
						$cache_time =0;

					} else {
						$cache_time =3600*8 +rand(0,3600);
					}

					$pedit =$this->process_paper_revisions( $p, $cache_time );
					//print_r( $pedit ); exit;

					if (!$cache_time) $this->data['papers'][$pcode] =$p;

					//$pedit =$this->request(sprintf( "/event/{id}/api/contributions/%d/editing/paper", $p['id'] ), 'GET', false, $pedit_options );

					$first_editing_state =false;
					foreach ($pedit['revisions'] as $rid =>$r) {
						if ($r['is_editor_revision']) {
							$reditor =$r['user']['full_name'];

							if (empty($editor_stats[$reditor])) {
								$editor_stats[$reditor] =$editor_stats_init;
								$editor_papers_list[$reditor] =[ 'g' =>false, 'y' =>false, 'r' =>false, 'a' =>false ];
								$editor_papers[$reditor] =0;
							}

							$editor_stats[$reditor]['revisions'] ++;

							if (!$first_editing_state) {
								$first_editing_state =empty($map_status[ $r['type']['name'] ]) ? $r['type']['name'] : $map_status[ $r['type']['name'] ];

								$editor_stats[$reditor][$first_editing_state] ++;
								$editor_papers[$reditor] ++;
								$editor_papers_list[$reditor][$first_editing_state][] =$p['id'];

								if ($p['qa_ok']) $editor_stats[$reditor]['qa_ok'] ++;

								$ymd =substr( $r['created_dt'], 0, 10 );
								$days['processed'][$ymd] =1 +(empty($days['processed'][$ymd]) ? 0 : $days['processed'][$ymd]);
							}						
						}

						if ($rid == $pedit['last_revision'] && $r['type']['name'] == 'ready_for_review') {
							$editor_stats[ $pedit['editor']['full_name'] ]['a'] ++;
						}
					}

/* 					if (empty($editor_stats[$editor])) {
						$editor_stats[$editor] =$editor_stats_init;
						$editor_papers_list[$editor] =[ 'g' =>false, 'y' =>false, 'r' =>false, 'a' =>false ];
					} */

/* 					if ($first_editing_state) {
						$editor_stats[$editor][$first_editing_state] ++;
						$editor_papers_list[$editor][$first_editing_state][] =$p['id'];
						$editor_papers[$editor] =1 +(empty($editor_papers[$editor]) ? 0 : $editor_papers[$editor]);
					}
 */
if (false) {
 					$istate =false; // initial state
					foreach ($pedit['revisions'] as $r_id =>$r) {
						$state =$r['type']['name'];

						if (!empty($r['editor']) && $r['editor']['full_name'] == $editor) {
							if ($this->debug) echo sprintf("%s | %s > %s\n", substr( $r['created_dt'], 0, 16 ), $istate, $state);
	
							if ($istate == 'ready_for_review' && !in_array( $state, [ 'ready_for_review', 'none', 'undone' ])) {
								if ($istatus == 'needs_submitter_confirmation' && !empty($pedit['revisions'][$r_id +1])) {
									$rn =$pedit['revisions'][$r_id +1];

									if ($rn['submitter']['full_name'] == $editor && $rn['type']['name'] == 'accepted') {
										$r =$rn;
										//$editor_stats[$editor]['revisions'] --;
										$istatus =$state;
										if ($this->debug) echo sprintf("%s | %s > %s\n", substr( $r['created_dt'], 0, 16 ), $istate, $state);
									}
								}
	
								$ymd =substr( $r['created_dt'], 0, 10 );
								$days['processed'][$ymd] =1 +(empty($days['processed'][$ymd]) ? 0 : $days['processed'][$ymd]);

								break;
							}
						}

						$istate =$state;
					} 
}

if (false) {					
					$ceditor =false;
					foreach ($pedit['revisions'] as $revision) {
						foreach ($revision['comments'] as $comment) {
							if (strpos($comment['text'], FAIL_QA_STRING)) {
/* 								if (empty($editor_stats[$ceditor])) {
									$editor_stats[$ceditor] =$editor_stats_init;
									$editor_papers_list[$ceditor] =[ 'g' =>false, 'y' =>false, 'r' =>false, 'a' =>false ];
								} */

//								$ceditor =$revision['submitter']['full_name'];
								$editor_stats[$ceditor]['qa_fail'] ++;
								echo "QA_FAIL: $pcode ($ceditor / $editor)\n";
							}
						}
						
						if (!empty($revision['editor'])) $ceditor =$revision['editor']['full_name'];
					}
}


					if ($paper_status == 'ready_for_review') $paper_status ='assigned';

					if (empty($istatus) || $istatus == 'none') $istatus =$paper_status;

					$istatus =$map_status[$istatus];

					echo sprintf( "%s - %s - %s (%s)\n", $pcode, substr( $r['created_dt'], 0, 10 ), $istatus, $r['type']['name'] );

/* 					if ($istatus != "" && $istatus != 'x') {
						$editor_stats[$editor][$istatus] =1 +(empty($editor_stats[$editor][$istatus]) ? 0 : $editor_stats[$editor][$istatus]);						
						$editor_papers_list[$editor][$istatus][] =$p['id'];
						$editor_papers[$editor] =1 +(empty($editor_papers[$editor]) ? 0 : $editor_papers[$editor]);
					}
 */
//					if ($p['qa_fail_count']) $editor_stats[$editor]['qa_fail'] ++;
				}

				$status =isset($map_status[ $paper_status ]) ? $map_status[ $paper_status ] : "_$paper_status";
				
				//if (strlen($status) == 1) echo "$pcode - $paper_status - $status\n";

				$qaok =($p['status_qa'] == 'QA Approved');

                if ($status != 'removed') {
                    $nums['total'] ++;
                    if ($qaok) $nums['qaok'] ++;
                }
                
                if (empty($nums[$status])) $nums[$status] =1;
                else $nums[$status] ++;

				if ($p['created_ts']) {
					$d =date( 'Y-m-d', $p['created_ts'] );
					if (empty($this->data['stats']['papers_submission'][$d])) $this->data['stats']['papers_submission'][$d] =1;
					else $this->data['stats']['papers_submission'][$d] ++;
				}
			}
		}
		
		ksort( $this->data['stats']['papers_submission'] );

		$nums['processed'] =$nums['g'] +$nums['y'] +$nums['r'];

		if (json_encode($nums) != json_encode($this->data['last_nums'])) {
			$tm =date( 'Y-m-d-H' );
			$this->data['stats'][$tm] =array_merge( $nums, [ 'ts' =>$now ]);
		}

		ksort( $days['processed'] );
		$this->data['stats']['days_processed'] =$days['processed'];

		//print_r( $nums );

		$this->data['last_nums'] =$nums;

		if (!empty($editor_papers)) {
			arsort( $editor_papers );
	
			foreach ($editor_papers as $e =>$n) {
				$eid =str_pad( $n, 3, '0', STR_PAD_LEFT ) .'|' .$e;
				//$eid =$e;
	
				$editors[$eid] =[
					'name' =>$e,
					'stats' =>$editor_stats[$e],
					'papers' =>$editor_papers_list[$e]
					];
			}
		}

/* 		echo "\n\n";
		print_r( $days );
		echo "\n\n"; */
		//print_r( $editors );

		$this->data['editors'] =$editors;
		$this->data['revisions'] =$revisions;
	}


    //-------------------------------------------------------------------------
    function import_registrants( $_details =true ) {
        global $cws_config;

/* 		$now =time();

        if (strtotime($this->cfg['dates']['registration']['from']) > $now
            || strtotime($this->cfg['dates']['registration']['to']) < $now
            ) {
				unset($this->cfg['out_registrants']);
				return false;
			}	 */	

		$this->verbose( "Process registrants" );

		$conf_registrants =[];
        $registrants =[];
        $stats =[];

        $this->cfg['cache_time'] =3600*24;

		$data_key2 =$this->request(sprintf( '/api/checkin/event/{id}/forms/%s/registrations', $cws_config['indico_stats_importer']['registrants_form_id'] ));
		foreach ($this->data[$data_key2] as $r) {
			$conf_registrants[ $r['id'] ] =[
				'ts' =>strtotime( $r['registration_date'] ),
				'paid' =>$r['is_paid'] ? $r['price'] : 0
				];
		}

/* 		print_r( $data_key2 );
		print_r( $this->data[$data_key2] );
		print_r( $conf_registrants ); */

		$data_key =$this->request( '/api/events/{id}/registrants' );
		
        foreach ($this->data[$data_key]['registrants'] as $r) {
			$rid =$r['registrant_id'];
            $p =$r['personal_data'];

            $type ='D';

			if (empty($conf_registrants[$rid])) { // is registered to another forms
				$ok =false;

			} else if (!empty($cws_config['make_chart_registrants']['skip_by_tags']) && !empty($r['tags'])) {
                foreach ($r['tags'] as $tag) {
                    if (in_array( $tag, $cws_config['make_chart_registrants']['skip_by_tags'] )) $ok =false;
                }

            } else {
                $ok =true;
            }

            if ($ok) {
                $registrants[$rid] =[
                    'surname' =>$p['surname'],
                    'name' =>$p['firstName'],
                    'email' =>$p['email'],
                    'inst' =>$p['affiliation'],
                    'nation' =>$p['country'],
                    'country' =>$p['country'],
                    'country_code' =>$p['country_code'],
                    'type' =>$type,
                    'tags' =>$r['tags'],
                    'present' =>$r['checked_in'],
					'ts' =>$conf_registrants[$rid]['ts'],
					'paid' =>$conf_registrants[$rid]['paid'],
					];
    
                if (!empty($r['tags'])) {
                    foreach ($r['tags'] as $tag) {
                        if (empty($stats['by_tag'][$tag])) $stats['by_tag'][$tag] =1;
                        else $stats['by_tag'][$tag] ++;
                    }
                }
                
                if (empty($stats['by_type'][$type])) $stats['by_type'][$type] =1;
                else $stats['by_type'][$type] ++;
            }         
        }

        foreach ([ 'by_dates', 'by_days_to_deadline', 'country', 'country_code'] as $k) {
            $stats[$k] =[];
        }

        $ts_deadline =strtotime($this->cfg['dates']['registration']['chart_to_deadline']);

        foreach ($registrants as $x) {
            $x['by_dates'] =date( 'Y-m-d', $x['ts'] );
            $x['by_days_to_deadline'] =-floor( ($ts_deadline -$x['ts']) /86400 );

            foreach ([ 'by_dates', 'by_days_to_deadline', 'country', 'country_code'] as $k) {
                if (empty($stats[$k][$x[$k]])) $stats[$k][$x[$k]] =1;
                else $stats[$k][$x[$k]] ++;  
            }            
        }

        ksort( $stats['by_dates'] );
        ksort( $stats['by_days_to_deadline'] );
        arsort( $stats['country'] );

        $this->data['registrants'] =array( 
            'registrants' =>$registrants,
            'stats' =>$stats
            ); 

        //print_r( $stats );
    }


    //-------------------------------------------------------------------------
    function import_abstracts() {
		$now =time();

/*         if (strtotime($this->cfg['dates']['abstracts_submission']['from']) < $now
            || strtotime($this->cfg['dates']['abstracts_submission']['to']) < $now
            ) {
				unset($this->cfg['out_abstracts_stats']);
				unset($this->cfg['out_persons']);
				return false;
			}
 */
		$this->verbose( "Process Abstracts List" );

		$data_key =$this->request( '/event/{id}/manage/abstracts/abstracts.json' );

        $persons =[];
        $abstracts =[];

		$withdrawn =0;

		$this->data['affiliations'] =[];
        $affiliations =&$this->data['affiliations'];

        foreach ($this->data[$data_key]['abstracts'] as $x) {
			if ($x['state'] != 'withdrawn') {
				$cf =[];
				foreach ($x['custom_fields'] as $cfa) {
					$cf[$cfa['name']] =$cfa['value'];
				}

				$abstracts[ $x['id'] ] =[			
					'title' =>$x['title'],
					'ts' =>strtotime( $x['submitted_dt'] )
					];

                foreach ($x['persons'] as $p) {
                    if (empty($persons[ $p['person_id'] ])) {
                        unset( $p['author_type'] );
                        unset( $p['is_speaker'] );

                        $persons[ $p['person_id'] ] =$p;
                    }

                    if (empty($affiliations[$p['affiliation']])) $affiliations[$p['affiliation']] =$p['affiliation_link'];

                    if (!empty($p['affiliation_link']['country_name'])) {
                        $affiliations[$p['affiliation']] =$p['affiliation_link'];
                    }					
                }

//				if (!empty($cf['Footnotes'])) print_r($abstracts[ $x['id'] ]);
			} else {
				$abstracts[ $x['id'] ] =[			
					'withdrawn' =>true,
					'ts' =>strtotime( $x['submitted_dt'] )
					];		
					
				$withdrawn ++;
			}
        }

        $this->data['abstracts_list'] =$abstracts;
        $this->data['persons'] =$persons;

        ksort( $affiliations );		

        $chart_by_dates =[];
        $chart_by_days_to_deadline =[];

        $ts_deadline =strtotime($this->cfg['dates']['abstracts_submission']['deadline']);

        foreach ($abstracts as $x) {
            $date =date( 'Y-m-d', $x['ts'] );
            if (empty($chart_by_dates[$date])) $chart_by_dates[$date] =1;
            else $chart_by_dates[$date] ++;        

            $days_to_deadline =-floor( ($ts_deadline -$x['ts']) /86400 );
            if (empty($chart_by_days_to_deadline[$days_to_deadline])) $chart_by_days_to_deadline[$days_to_deadline] =1;
            else $chart_by_days_to_deadline[$days_to_deadline] ++;                
        }
    
        ksort( $chart_by_dates );
        ksort( $chart_by_days_to_deadline );

        $this->data['abstracts_stats']['by_dates'] =$chart_by_dates;
        $this->data['abstracts_stats']['by_days_to_deadline'] =$chart_by_days_to_deadline;
        $this->data['abstracts_stats']['count'] =count( $abstracts );
        $this->data['abstracts_stats']['withdrawn'] =$withdrawn;
    }

	//-------------------------------------------------------------------------
	function import() {
        switch (APP) {
            case 'indico_stats_importer': return $this->import_stats();
            case 'make_page_participants': return $this->import_registrants();
            case 'make_chart_registrants': return $this->import_registrants();
            case 'make_chart_abstracts': return $this->import_abstracts();
        } 

		$prev_papers =$this->data['papers'];

		$abstracts =[];
		$papers =[];
		$authors_db =[];

		$programme =[
            'sessions' =>[],
			'classes' =>[],
			'rooms' =>[],
			'days' =>[]
            ];

		$map_status =MAP_STATUS;

		$this->verbose( "Process contributions" );

		$papers_submission_ok =(strtotime($this->cfg['dates']['papers_submission']['from']) < time());

		$editing_status =[];
		if ($papers_submission_ok) {
//			https://indico.jacow.org/event/41/editing/api/paper/file-type

			$source_file_type_id =false;
			$types =$this->request( '/event/{id}/editing/api/paper/file-types', 'GET', false, 
				[ 'return_data' =>true, 'quiet' =>false ]);

			foreach ($types as $x) {
				if (strtolower($x['name']) == 'source files') $this->source_file_type_id =$x['id'];
			} 

			$data_key_editing_status =$this->request( '/event/{id}/editing/api/paper/list' );

			//$papers_revision =[];
			foreach ($this->data[$data_key_editing_status] as $x) {
				if (!empty($x['editable'])) $editing_status[ $x['code'] ] =$x['editable'];

//				$papers_revision[$x['code']] =empty($x['editable']['revision_count']) ? 0 : $x['editable']['revision_count'];
			}

			//print_r( $editing_status ); return;
		}
//		$papers_submission_ok =true;

		$data_key_timetable =$this->request( '/export/timetable/{id}.json' );

		foreach ($this->data[$data_key_timetable]['results'][$this->event_id] as $day) {
			foreach ($day as $s) {
				if (!empty($s['entries'])) {
					if (!in_array( $s['code'], $this->cfg['papers_hidden_sessions'] ) && !empty($s['code'])) {
//                        $programme['sessions'][ $s['code'] ] =[ 'code' =>$s['code'] ];
                        $programme['sessions'][ $s['sessionSlotId'] ] =[ 
							'code' =>$s['code'],
							'slotTitle' =>$s['slotTitle']
							];
                    }

					foreach ($s['entries'] as $c) {										
						if (!empty($c['code'])) {
							$pcode =$c['code'];

							$c_ts_from =strtotime( $c['startDate']['date'] .' ' .$c['startDate']['time'] );
							$c_ts_to =strtotime( $c['endDate']['date'] .' ' .$c['endDate']['time'] );
			
							$presenter =empty($c['presenters'][0]) ? false : $c['presenters'][0];
//							$author =empty($c['primaryauthors'][0]) ? false : $c['primaryauthors'][0];

							$this->verbose( "$pcode | $c[title]", 4 );
	
							$p =[
								'id' =>$c['contributionId'],
								"abstract_id" =>$c['friendlyId'],
								'session_code' =>$s['code'],
								'session_id' =>$s['sessionSlotId'],
								'code' =>$pcode,
								'title' =>$c['title'],
								'type' =>false,
								'poster' =>$s['isPoster'],
								"time_from" =>date( 'H:i', $c_ts_from ),
								"time_to" =>date( 'H:i', $c_ts_to ),
								"tsz_from" =>$c_ts_from,
								"tsz_to" =>$c_ts_to,
								"abstract" =>!empty($c['description']),
								"primary_code" =>"Y",
								"presenter" =>$presenter ? sprintf( "%s %s - %s", $presenter['firstName'], $presenter['familyName'], $presenter['affiliation'] ) : false,
								"presenter_email" =>$presenter ? $presenter['email'] : false,
                                "author" =>false,
                                "author_inst" =>false,
                                "authors" =>false,
								"authors_names" =>false,
								"authors_emails" =>false,
								"authors_by_inst" =>false,
								"source_type" =>false,
								"pdf_url" =>false,
								"created_ts" =>false,
//								'prev_status' =>false,
								"status" =>false,
                                "status_ts" =>false,
								"status_indico" =>false,
								"paper_state" =>empty($editing_status[$pcode]) ? false : $editing_status[$pcode]['state'],
								"revision_count" =>empty($editing_status[$pcode]) ? false : $editing_status[$pcode]['revision_count'],
								"rev_id" =>false, // revision id (rev_count - status - tags)
								"tags" =>false,
								"status_qa" =>false,
								"qa_ok" =>false,
//								"qa_fail_count" =>0,
								"editor" =>false,
								"hide" =>in_array( $s['code'], $this->cfg['papers_hidden_sessions'] )
                                ];		

							$papers[$pcode] =$p;

							//if ($pcode == 'MOXA1') print_r( $p );
		
							$abstracts[ $pcode ] =[
								"text" =>$c['description'],
								"footnote" =>"",
								"agency" =>""                        
								];
						}
					}
				}
			}
		}

		$this->verbose( count($papers) ." contributions found\n" );

		$this->verbose( "Process sessions" );

		$data_key_event =$this->request( '/export/event/{id}.json', 'GET', 
			[ 'detail' =>'sessions' ]);

		if (!empty($this->data[$data_key_event]['results'][0]['sessions'])) {
			foreach ($this->data[$data_key_event]['results'][0]['sessions'] as $sb) {
				$s =$sb['session'];
	
				$chair =empty($s['sessionConveners'][0]) ? false : $s['sessionConveners'][0];
	
				$s_ts_from =strtotime( $sb['startDate']['date'] .' ' .$sb['startDate']['time'] );
				$s_ts_to =strtotime( $sb['endDate']['date'] .' ' .$sb['endDate']['time'] );
	
				$day =$s['startDate']['date'];
	
				$this->verbose( "$day - $sb[code] ($sb[room] | $s[room]) - $sb[title]" );
	
				$session_key =date( 'Hi', $s_ts_from )
					.'_' .str_replace( ' ', "", $sb['room'] )
					.'_' .$sb['code']
					.'_' .$sb['id']
					;
	
				$session_papers =[];
				foreach ($sb['contributions'] as $c) {
					$pcode =$c['code'];

					$c_ts_from =strtotime( $c['startDate']['date'] .' ' .$c['startDate']['time'] );
					$c_ts_to =strtotime( $c['endDate']['date'] .' ' .$c['endDate']['time'] );
	
                    if (!empty($papers[$pcode])) {
                        $p =$papers[$pcode];
						
						if (!empty($papers[$pcode]['revision_count']) && $papers_submission_ok) {
							if (empty($prev_papers[$pcode]) || empty( $editing_status[$pcode] )) {
								$new_revision =true;

							} else {
								$p['rev_id'] =$this->get_rev_id( $editing_status[$pcode] );

								$new_revision =$p['rev_id'] != $prev_papers[$pcode]['rev_id'];
							}

							$rqst_cache =$new_revision ? 0 : 3600*8; //3600*24*30;

							$this->process_paper_revisions( $p, $rqst_cache );
						}

                        $p['class'] =$c['track'];
                                              
                        $author =empty($c['primaryauthors'][0]) ? false : $c['primaryauthors'][0];
                        if ($author) {
                            $p['author'] =sprintf( "%s %s", $author['first_name'], $author['last_name'] );
                            $p['author_inst'] =$author['affiliation'];
                        }
                                
                        $p['authors'] =false;
						$p['authors_by_inst'] =false;
						$primary =true;
                        foreach (array_merge( $c['primaryauthors'], $c['coauthors'] ) as $author) {
							$author_name =$this->author_name( $author );

							$p['authors'] .=($p['authors'] ? ', ' : false) .$author_name;
							$p['authors_by_inst'][$author['affiliation']][] =$author_name;
							
                            $p['authors_names'][] =$author_name;
							if (!empty($author['email'])) $p['authors_emails'][] =$author['email'];

							$aid =trim($author['last_name']) .'|' .trim($author['first_name']) .'|' .trim($author['affiliation']);

							if (empty($authors_db[$aid])) {
								$authors_db[$aid] =[
									'id' =>$author['id'],
									'affiliation' =>$author['affiliation'],
									'name' =>$author_name,
									'first_name' =>trim($author['first_name']),
									'last_name' =>trim($author['last_name']),
									'email' =>$author['email']
									];
							}

							if (!empty($p['status'])) $authors_db[$aid]['papers'][$pcode] =[
								'status' =>$p['qa_ok'] ? 'qaok' : $p['status'],
								'id' =>$p['id'],
								'primary' =>$primary
								];

							$primary =false;
						}
						
                        $p['type'] =$c['type'];
     
                        $session_papers[$pcode] =$p;
                        $papers[$pcode] =$p;
                    }
				}
	
				ksort( $session_papers );
	
				$room =preg_replace("/[^a-zA-Z0-9]+/", "", $sb['room'] );
				if (isset($programme['rooms'][$room])) $programme['rooms'][$room] ++;
				else $programme['rooms'][$room] =1;
	
				$programme['days'][$day][$session_key] =[
					'id' =>$sb['id'],
					'code' =>$sb['code'],
					'type' =>$s['isPoster'] ? "Poster Session" : $s['type'],
					'poster_session' =>$s['isPoster'],
					"class" =>"",
					"title" =>$sb['title'],
					"chair" =>$chair ? "$chair[first_name] $chair[last_name]" : false,
					"chair_inst" =>$chair ? "$chair[affiliation]" : false,
					"time_from" =>date( 'H:i', $s_ts_from ),
					"time_to" =>date( 'H:i', $s_ts_to ),
					"tsz_from" =>$s_ts_from,
					"tsz_to" =>$s_ts_to,
					"room" =>$room,
					"location" =>$sb['room'],
					'papers' =>$session_papers
					];

/*                 if (!empty($programme['sessions'][ $sb['code'] ])) {
                    $programme['sessions'][ $sb['code'] ] =$programme['days'][$day][$session_key];
                    $programme['sessions'][ $sb['code'] ]['papers'] =array_keys( $session_papers );
                }  */                   

                if (!empty($programme['sessions'][ $sb['id'] ])) {
                    $programme['sessions'][ $sb['id'] ] =$programme['days'][$day][$session_key];
                    $programme['sessions'][ $sb['id'] ]['papers'] =array_keys( $session_papers );
                }                    
			}
		}

        ksort( $programme['days'] );

		foreach ($programme['days'] as $day =>$d) {
			$programme['days'][$day]['999999_END'] ='END';
			ksort( $programme['days'][$day] );
		}

		$this->verbose( "" );

		//print_r( $papers['MOXA1'] );

		$this->data['abstracts'] =$abstracts;	
		$this->data['papers'] =$papers;
		$this->data['programme'] =$programme;
		$this->data['editing_tags'] =$this->editing_tags;

		ksort( $authors_db );
		$this->data['authors'] =$authors_db;

		print_r( $programme['rooms'] );
	}

	//-----------------------------------------------------------------------------
	function cleanup( $_unlink =true ) {
		$cfg =$this->cfg;

		$this->verbose( "Remove temporary files ($cfg[tmp_path])... ", 1, false );
		if ($_unlink) system( "rm -f $cfg[tmp_path]/*" );
		$this->verbose_ok();

		foreach ($cfg as $var =>$val) {
			if (substr( $var, 0, 4 ) == 'out_' && strpos( $var, '_path') === false && file_exists( $val )) {
				$this->verbose( "Remove ($var) $val... " );
				
                if ($_unlink) $this->verbose_status( !unlink( $val ) );
                else $this->verbose_next( "SKIP" );
			}
		}
	} 

	//-----------------------------------------------------------------------------
	function save_all( $_cfg =false ) {
		foreach ($this->cfg as $c =>$fname ) {
			if ($c != 'out_path' && substr( $c, 0, 4 ) == 'out_') {
				$this->save_file( substr( $c, 4 ), $c, false, $_cfg );
			}
		}
	}

	//-----------------------------------------------------------------------------
	function save_file( $_data_id, $_file_id, $_label =false, $_cfg =false ) {
        $cfg =[
            'counter' =>true,
            'save_empty' =>false
			];

        if (!empty($_cfg) && is_array($_cfg)) {
            foreach ($_cfg as $key =>$val) {
                $cfg[$key] =$val;
            }
        }

		if (empty($_label)) $_label =strtoupper( $_data_id );

		$fname =$this->cfg[$_file_id];
		$this->verbose( "# Save $_label Data ($fname)... ", 2, false );

        if (empty($this->data[$_data_id]) && empty($cfg['save_empty'])) {
            $this->verbose_next( "NO_DATA" );
            return;
        }

        if ($cfg['counter']) {
            if (is_numeric($cfg['counter'])) $counter =sprintf( ' (%d)', $cfg['counter'] );
			else if (!empty($this->data[$_data_id])) $counter =sprintf( ' (%d)', count($this->data[$_data_id]) );

        } else {
            $counter =false;
        }

		$this->verbose_status( !file_write_json( $fname, $this->data[$_data_id] ), "Unable to write file $fname", "OK" .$counter );
	}



	//-----------------------------------------------------------------------------
	function export_refs( $_fname =false, $_final =false ) {
		
		$out_fname =$_fname ? $_fname : $this->cfg['export_refs'];
		
		$this->verbose( "# Save REFS data (" .$out_fname .")... ", 1, false );
		$citations =false;

		foreach ($this->data['papers'] as $pid =>$p) {
			if (!empty($p['authors']) && !in_array( $p['session_code'], $this->cfg['refs_hidden_sessions'] )) {
				$citations[] =array(
					'paper' =>$pid,
					'authors' =>$p['authors'],
					'title' =>$p['title'],
					'position' =>$p['position'] ?? false,
					'contribution ID' =>$p['abstract_id']
					);
			}
		}
		
		if ($citations) {
			$fp =fopen( $out_fname, 'w' );
			fputcsv( $fp, array_keys( $citations[0] ) );
			foreach ($citations as $cit) {
				fputcsv( $fp, $cit );
			}
			fclose( $fp );

			$this->verbose_ok( "(" .count($citations) .") " );
			
		} else {
			$this->verbose_error( "(No data)" );
		}
	}



	//-----------------------------------------------------------------------------
	function export_refs_v3( $_fname =false ) {
		
		$out_fname =$_fname ? $_fname : $this->cfg['export_refs'];
		
		$this->verbose( "# Save REFS data (" .$out_fname .")... ", 1, false );
		$citations =false;

		foreach ($this->data['papers'] as $pid =>$p) {
			if (!empty($p['authors']) && !in_array( $p['session_code'], $this->cfg['refs_hidden_sessions'] )) {
				$citations[] =array(
					'PaperId' =>$pid,
					'Authors' =>$p['authors'],
					'Title' =>$p['title'],
					'PageRange' =>$p['position'],
					'Contribution_ID' =>$p['abstract_id'],
					'DOI' =>$p['position'] ? '10.18429/JACoW-IPAC2023-' .$pid : false,
					'PubStatus' =>$p['position'] ? 1 : 3,
					'SPMS_Id' =>41,
					'Publication_Date' =>'9,2023'
					);
			}
		}
		
		if ($citations) {
			$fp =fopen( $out_fname, 'w' );
			fputcsv( $fp, array_keys( $citations[0] ) );
			foreach ($citations as $cit) {
				fputcsv( $fp, $cit );
			}
			fclose( $fp );

			$this->verbose_ok( "(" .count($citations) .") " );
			
		} else {
			$this->verbose_error( "(No data)" );
		}
	}



	//-----------------------------------------------------------------------------
	function import_posters() {
		$PP =false;
		$poster_count =0;
			
 		foreach ($this->data['programme']['days'] as $day =>$odss) { // ObjDaySessions
			foreach ($odss as $id =>$os) { // ObjSession
				if (is_array($os) && !empty($os['poster_session'])) {
					$sid =$os['code'];
					
					$PP[$day][$sid] =array( 
						'code' =>$sid,
						'type' =>$os['type'],
						'title' =>$os['title'],
						'location' =>$os['location']
					    );			
					
					$digits =false;
					foreach ($os['papers'] as $pid =>$op) { // ObjPoster					
						if (!$digits) {
							$digits =strlen($pid) -strlen($sid);
						}

						$pn =substr( $pid, -$digits );
						$PP[$day][$sid]['posters'][$pn] =array(
							'code' =>$pid,
							'title' =>$op['title'],
							'presenter' =>$op['presenter'],
							'abstract_id' =>$op['abstract_id']
							);

						$poster_count ++;
					}

					if (!empty($PP[$day][$sid]['posters'])) ksort( $PP[$day][$sid]['posters'] );
				}
			}
		}
		
        $this->data['posters'] =$PP;

//        $this->save_file( 'posters', 'out_posters', 'POSTERS', $poster_count );
	}	

	//-----------------------------------------------------------------------------
	function GoogleChart( $_data =false ) {
		extract( $this->cfg );
		
//		list( $type, $what ) =explode( ',', $xtract );
		
		$var =$y_title;

//		if ($startdate && strpos( $startdate, '-' )) $startdate =strtr( $startdate, '-', ',' );
		
//		$data =$this->xtract( $type, $what, true );

        if ($_data) $data =$_data;
        else $data =$this->data[$source];
		
		if (!$data) {
			$this->verbose_error( "ERROR: no data" );
			return;
		}
		
		$i =0;
		$n =0;
		$addrow =false;
		foreach ($data as $date =>$value) {
				if ($value[0]) {
					if ($i == 0 && $startdate) {
                        list( $dy, $dm, $dd ) =explode( '-', $startdate );
                        $dm --;
                        $addrow .=" data.addRow([new Date($dy,$dm,$dd),0]);\n";
                    }
				
					list( $dy, $dm, $dd ) =explode( '-', $date );
					$dm --;
					$dd +=0;

					$n +=$value[0];
					$addrow .=" data.addRow([new Date($dy,$dm,$dd),$n]);\n";
					
					$i ++;
				}
		}

	//	$this->verbose_ok( "OK ($i records)" );
		
		echo "\n";

		$width =CHART_WIDTH;
		$height =CHART_HEIGHT;
		
		$color1 =$this->cfg['colors']['primary'];
		$color2 =$this->cfg['colors']['secondary'];
		
		$js =APP_OUT_JS;
				
		foreach (array( 'html', 'js' ) as $ftype) {
			$tmpl =$this->cfg['chart_'.$ftype];
			if ($tmpl) {
				$template =file_read( $tmpl );
				
				eval( "\$out =\"$template\";" );
				file_write( $this->cfg['out_path'] .'/' .($ftype == 'html' ? APP_OUT_HTML : APP_OUT_JS), $out, 'w', true, $ftype );
				
				echo "\n";
			}
		}
	}

	//-------------------------------------------------------------------------
	function parse_template( $_vars, $_template ='template', $_out ='out_html') {
		$tmpl =$this->cfg[$_template];
		
//		echo "Read template $tmpl\n";

		if ($tmpl) {
			$page =file_read( $tmpl );
			
			foreach ($_vars as $var =>$value) {
				$page =str_replace( '{'.$var.'}', $value, $page );
			}
			
			file_write( $this->cfg[$_out], $page, 'w', true );
			
			//			echo "\n";
			
			return $page;
		}
	}
	
	//-------------------------------------------------------------------------
	function author_name( $_author ) {
		$fn0 =str_replace( '-', ' -', trim($_author['first_name']) );
		$fn_p_list =explode( ' ', $fn0 );
		
		$fn =false;
		foreach ($fn_p_list as $fn_p) {
			$fn .=(substr( $fn_p, 0, 1 ) == '-' ? substr( $fn_p, 0, 2 ) : substr( $fn_p, 0, 1 )) .'.';
		}
		
		return $fn .' ' .trim($_author['last_name']);  
	}
	
	//-------------------------------------------------------------------------
	function get_rev_id( $_x ) {
		if (empty($_x)) return false;

		$rev_id =$_x['revision_count'] .'-' .$_x['state'];
		if (!empty($_x['tags'])) {
			foreach ($_x['tags'] as $tag) {
				if (substr( $tag['code'], 0, 2 ) == 'QA') $rev_id .='-' .$tag['code'];
			}
		}		

		return $rev_id;
	}

	//-------------------------------------------------------------------------
	function process_paper_revisions( &$_paper, $_cache_time =0 ) {

		$map_status =MAP_STATUS;

		$pedit =$this->request( "/event/{id}/api/contributions/$_paper[id]/editing/paper", 'GET', false, 
			[ 'return_data' =>true, 'quiet' =>true, 'cache_time' =>$_cache_time ]);

		if (empty($pedit['error'])) {
			if (!empty($pedit['state'])) {
				$_paper['status_indico'] =$pedit['state']['title'];

				$paper_status =$pedit['state']['name'];
				if (!empty($pedit['editor']) && $paper_status == 'ready_for_review') $paper_status ='assigned';										
				$_paper['status'] =isset($map_status[ $paper_status ]) ? $map_status[ $paper_status ] : "_$paper_status";
			}

			if (!empty($pedit['editor'])) $_paper['editor'] =$pedit['editor']['full_name'];

			if (!empty($pedit['revisions'])) {
				$ri =0; // number revisions (excluded undone)
				$lrid =false;
				foreach ($pedit['revisions'] as $rid =>$revision) {
					if (empty($revision['is_undone'])) {
						if ($ri == 0) {
							$_paper['created_ts'] =strtotime( $revision['created_dt'] );

							foreach ($revision['files'] as $f) {
								if ($f['file_type'] == $this->source_file_type_id) $_paper['source_type'] =strtolower(pathinfo( $f['filename'], PATHINFO_EXTENSION ));
							}

							$pedit['first_revision'] =$rid;
						}

						if (!empty($revision['files'])) { 
							foreach ($revision['files'] as $f) {
								if ($f['filename'] == $_paper['code'] .".pdf") $_paper['pdf_url'] =$f['external_download_url'];
							}
						}

						$paper_tags =[];
						foreach ($revision['tags'] as $tag) {
							if (substr( $tag['code'], 0, 2 ) == 'QA') {
								$_paper['status_qa'] =$tag['title'];
								if ($_paper['status_qa'] == 'QA Approved') $_paper['qa_ok'] =true;

							} else if (!$tag['system']) {
								$paper_tags[] =$tag['verbose_title'];	
							}
						}

						foreach (array_unique($paper_tags) as $tag) {
							if (empty($this->editing_tags[$tag])) $this->editing_tags[$tag] =1;
							else $this->editing_tags[$tag] ++;
						}

						$ri ++;
						$lrid =$rid;
						
					} else {
						unset($pedit['revisions'][$rid]);
					}
				}
				
				$pedit['revisions_count'] =$ri;

				// about last revision
				$pedit['last_revision'] =$lrid;
				$revision =$pedit['revisions'][$lrid];
				
				$_paper['status_ts'] =strtotime( $revision['created_dt'] );

/* 				foreach ($revision['comments'] as $comment) {
					if (strpos( $comment['text'], FAIL_QA_STRING )) $_paper['status_qa'] ='QA Failed';
				} */
			}
		}

		return $pedit;
	}



} /* END CLASS */

?>
