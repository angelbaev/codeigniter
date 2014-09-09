<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		Angel Baev
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * PHP QR Code Adapter Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Validation
 * @author		Angel Baev
 * @link		
 */

class CI_QR_Adapter {
    private $qrcode = null;
    protected $CI;
    
    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct($props = array()) {
    /*
        if (count($props) > 0) {
            $this->initialize($props);
        }
        */
        $this->load('qrlib.php');
        $this->qrcode = new QRcode();
        log_message('debug', "QR Adapter Class Initialized");
    }
    
    public function __call($method, $arguments) {
        return call_user_func_array(array($this->qrcode, $method), $arguments);
    }

    /**
     * Initialize preferences
     *
     * @param	array
     * @return	void
     */
    public function initialize($config = array()) {
        $defaults = array(
            'PNG_TEMP_DIR' => dirname(__FILE__).DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR,
            'PNG_WEB_DIR' => 'temp/',
            'file_name' => '',
            'errorCorrectionLevel' => 'L',
            'matrixPointSize' => 4,
        );


        foreach ($defaults as $key => $val) {
            if (isset($config[$key])) {
                $method = 'set_' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($config[$key]);
                } else {
                    $this->$key = $config[$key];
                }
            } else {
                $this->$key = $val;
            }
        }
        
        if(in_array($this->errorCorrectionLevel, array('L','M','Q','H'))) {
          $this->errorCorrectionLevel = 'L';
        }
        $this->matrixPointSize =  min(max((int)$this->matrixPointSize, 1), 10);
    }
    
    private function load($file_name) {
       $real_path = realpath(APPPATH.'/third_party/phpqrcode/');
       if(!empty($real_path)) {
         $real_path = $real_path.DIRECTORY_SEPARATOR;
         $file = $real_path.$file_name;
         if(file_exists($file) && is_file($file) && is_readable($file)) {
            include_once ($file);
         } else {
           $this->set_error('no_perm');
         }
       } else {
          $this->set_error('not_instaled');
       }
    }
    


    /**
     * Set an error message
     *
     * @param	string
     * @return	void
     */
    public function set_error($msg) {
        $CI = & get_instance();
        $CI->lang->load('QR_Adapter');

        if (is_array($msg)) {
            foreach ($msg as $val) {
                $msg = ($CI->lang->line($val) == FALSE) ? $val : $CI->lang->line($val);
                $this->error_msg[] = $msg;
                log_message('error', $msg);
            }
        } else {
            $msg = ($CI->lang->line($msg) == FALSE) ? $msg : $CI->lang->line($msg);
            $this->error_msg[] = $msg;
            log_message('error', $msg);
        }
    }

    /**
     * Display the error message
     *
     * @param	string
     * @param	string
     * @return	string
     */
    public function display_errors($open = '<p>', $close = '</p>') {
        $str = '';
        foreach ($this->error_msg as $val) {
            $str .= $open . $val . $close;
        }

        return $str;
    }

}
