<?php

/* bY Stefano.Deiuri@Elettra.Eu

2022.07.20 - update for Indico

*/

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

define( 'CFG_VERSION', 2 );

class DOTTING_BOARD extends CWS_OBJ {
    var $ret;

	//-------------------------------------------------------------------------
	function __construct( $_cfg =false ) {
        parent::__construct( $_cfg );
        
        $this->ret =array(
            'cfg' =>array(
                'version' =>CFG_VERSION
                )
            );
    }
        
    //-------------------------------------------------------------------------
    function init_ret() {
        if (APP_PAPER_STATUS_QRCODE) {
            $qrcode_img ='../html/qrcode_app_paper_status.png';
    
            if (!file_exists( $qrcode_img )) {
                require( '../libs/phpqrcode/qrlib.php' );
                $qrcode_content =APP_PAPER_STATUS_URL ? APP_PAPER_STATUS_URL : ROOT_URL .'/app_paper_status';
                QRcode::png( $qrcode_content, $qrcode_img, 'L', 4 );
                
                $png =imagecreatefrompng( $qrcode_img );
                $bg = imagecolorat( $png, 0, 0 );
                imagecolorset( $png, $bg, 85, 85, 85 );
                imagepng( $png, $qrcode_img );
            }

        } else {
            $qrcode_img =false;
        }
        
        $legend =false;
        foreach ($this->cfg['labels'] as $name =>$desc) {
            $legend[$name] =$desc;
        }	
        
        $n_dots =count( $this->data['edots'] );
        
        foreach ($this->data['edots'] as $paper_id =>$p) {
            if ($p['status'] == 'removed') $n_dots --;
/*             if ($p['status'] == 'removed' || $p['pc'] != 'Y') $n_dots --; */
        }
    
        if (!APP_BOARD_COLS) {
            $cols =($n_dots > 500 ? 12 : 7);
            $rows =min( 19, ceil( $n_dots / $cols ));
        
        } else {
            $cols =APP_BOARD_COLS;
            $rows =APP_BOARD_ROWS;
        }
        
        $this->ret['cfg'] =array(
            'version' =>CFG_VERSION,
            'conf_name' =>CONF_NAME,
            'change_page_delay' =>APP_CHANGE_PAGE_DELAY,
            'reload_data_delay' =>APP_RELOAD_DATA_DELAY, // seconds	
            'cols' =>$cols,
            'rows' =>$rows,
            'legend' =>$legend,
            'qrcode' =>$qrcode_img,
            'qrcode_cells' =>$qrcode_img ? $this->cfg['qrcode_cells'] : 0,
            'dots' =>$n_dots,
            'pages' =>ceil( $n_dots /($cols * $rows))
            );	
    }

    //-------------------------------------------------------------------------
    function reply() {
        if (empty($ts_rqst)) $this->init_ret();
    
        $map_days =array( 'mo' =>1, 'tu' =>2, 'we' =>3, 'th' =>4, 'fr' =>5 );
        
        foreach ($this->data['edots'] as $paper_id =>$p) {
//            if (($p['pc'] == 'Y' || $p['status'] == 'removed') && $p['ts'] > $ts_rqst) {
            if (empty($ts_rqst) || $p['status_ts'] > $ts_rqst) {
                $status =$p['status'];
                if ($status == 'nofiles') $status ='';
                $class =($p['qa_ok'] ? 'qaok' : $status);				
                
                $day =strtolower(substr($paper_id,0,2));
                
                $this->ret['edots'][$map_days[$day].$paper_id] =$class;
            }
        }
        
        ksort( $this->ret['edots'] );
        
        $this->ret['title'] =CONF_NAME .' ' .APP_NAME;
        
        $this->ret['ts'] =time();
        
        if ($_GET['debug']) {
            echo "<pre>";
            $cfg['spms_passphrase'] ='****';
            print_r( $this->cfg );
            print_r( $this->ret );
            return;
        }
        
        gz_http_response( json_encode( $this->ret ) );
    }

}

$db =new DOTTING_BOARD(config( 'page_edots', true ));
$db->load();
$db->reply( $_GET['ts'] ); 

?>