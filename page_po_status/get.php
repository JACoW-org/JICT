<?php

/* bY Stefano.Deiuri@Elettra.Eu

2023.05.09 - update
2022.08.29 - update

*/

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'jict', '1.0' );

define( 'CFG_VERSION', 6 );

class PO_STATUS extends CWS_OBJ {
    var $ret;

	//-------------------------------------------------------------------------
	function __construct( $_cfg =false ) {
        parent::__construct( $_cfg );
        
        $this->ret =[
            'cfg' =>[ 'version' =>CFG_VERSION ]
            ];
    }

    //-------------------------------------------------------------------------
    function reply( $_ts_rqst =false ) {
        if (empty($_ts_rqst)) {
            $this->ret['cfg'] =[
                'version' =>CFG_VERSION,
                'conf_name' =>CONF_NAME,
                'change_page_delay' =>10, // seconds
                'reload_data_delay' =>30, // seconds	
                'history_date_start' =>$this->cfg['dates']['proceedings_office']['history_from']
                ];

            $_ts_rqst =strtotime( $this->cfg['dates']['proceedings_office']['history_from'] );
        }

        if (!empty($this->cfg['hidden_sessions'])) {
            $papers =false;
            foreach ($this->data['papers'] as $paper_id =>$p) {
                if (!in_array( $p['session_code'], $this->cfg['hidden_sessions'])) {
                    $papers[$paper_id] =$p;
                }
            }

        } else {
            $papers =$this->data['papers'];
        }

        foreach ($papers as $pcode =>$p) {
            //$cws_config['page_edots']['hidden_sessions']
            $status =$p['status'];
            if (empty($status) || $status == 'nofiles') $status ='';
            
            $class =($p['qa_ok'] ? 'qaok' : $status);				
            $this->ret['edots'][$pcode] =$class;
        }
        
        $this->ret['history'] =[];
        foreach ($this->data['stats'] as $sid =>$s) {
            if ($s['ts'] > $_ts_rqst) $this->ret['history'][$sid] =$s;
        }

        $this->ret['editors'] =$this->data['editors'];
        
        $this->ret['ts'] =time();
        
        $this->ret['colors'] =[];
        foreach ([ 'files', 'a', 'qaok', 'g', 'y', 'r', 'nofiles' ] as $cname) {
            $this->ret['colors'][] =$this->cfg['colors'][$cname];
        }
        
        $this->ret['labels'] =$this->cfg['labels'];
        
        if ($_GET['debug']) {
            echo "<pre>";
            print_r( $this->ret );
            return;
        }

        gz_http_response( json_encode( $this->ret ) );
    }
}

$page =new PO_STATUS(config( 'page_po_status', true ));
$page->load();
$page->reply( $_GET['ts'] ); 

?>