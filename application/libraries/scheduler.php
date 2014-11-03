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
 * PHP user angent class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Validation
 * @author		Angel Baev
 * @link		
 */

class CI_Scheduler {
    protected $CI;
    private $types = array();
    private $states = array();
    
    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->database();
        $this->CI->load->library('session');
        $this->types = array('system', 'mobile');
        $this->states = array('init', 'queue', 'done', 'approve');
       // if(is_null($this->user_id)) $this->user_id = $this->CI->session->userdata('user_id');

        log_message('debug', "CI_Scheduler Initialized");
    }
    
    public function get($user_id) {
        try {
            $this->CI->db->select('usc.user_id, usc.scheduler_token, s.device_token, s.command, s.type, s.state, s.info, s.start_time, s.modified_time');
            $this->CI->db->from('e_user_scheduler as usc');
            $this->CI->db->join('e_scheduler as s', 's.user_id = usc.user_id', 'INNER');
            $this->CI->db->where('usc.user_id', $user_id);
            $this->CI->db->limit(1);
            $query = $this->CI->db->get();

            return $query->row();
        }
        catch(Exception $e){
           log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
           return FALSE;
        }         
    
    }

    public function get_all($user_id) {
        try {
            $this->CI->db->select('usc.user_id, usc.scheduler_token, s.device_token, s.command, s.type, s.state, s.info, s.start_time, s.modified_time');
            $this->CI->db->from('e_user_scheduler as usc');
            $this->CI->db->join('e_scheduler as s', 's.user_id = usc.user_id', 'INNER');
            $this->CI->db->where('usc.user_id', $user_id);
            $query = $this->CI->db->get();

            return $query->result();
        }
        catch(Exception $e){
           log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
           return FALSE;
        }         
    
    }
    
    public function get_scheduler_token($user_id) {
        try {
            $this->CI->db->select('scheduler_token');
            $this->CI->db->from('e_user_scheduler');
            $this->CI->db->where('user_id', $user_id);
            $this->CI->db->limit(1);
            $query = $this->CI->db->get();

            return (isset($query->row()->scheduler_token)?$query->row()->scheduler_token:'');
        }
        catch(Exception $e){
           log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
           return FALSE;
        }         
    }

    public function add($user_id, $scheduler_token, $data) {
        try {
          $this->delete($user_id);
          $insert = $this->CI->db->insert('e_user_scheduler', array('user_id' => $user_id, 'scheduler_token' => $scheduler_token));
          
          if($insert) {
            $type = 'system';
            $state = 'init';
            if(isset($data['type']) && !in_array($data['type'], $this->types)) {
               $type = $data['type']; 
            }
            if(isset($data['state']) && !in_array($data['state'], $this->states)) {
               $state = $data['state']; 
            }
            $params = array(
              'scheduler_token' => $scheduler_token,
              'user_id' => $user_id,
              'command' => (isset($data['command'])?$data['command']:''),
              'requesttype' => (isset($data['requesttype'])?$data['requesttype']:''),
              'device_token' => $this->getDeviceToken($user_id),
              'type' => $type,
              'state' => $state,
              'info' => (isset($data['info'])?$data['info']:''),
              'start_time' => date('Y-m-d H:i:s'),
              'modified_time' => date('Y-m-d H:i:s'),
            );
            $insert2 = $this->CI->db->insert('e_scheduler', $params);
            return $insert2;
          } else {
            return $insert;
          }
          
        }
        catch(Exception $e){
           log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
           return FALSE;
        }                
    }
    
    
    public function update($user_id, $data) {
        try {
            $type = 'system';
            $state = 'init';
            if(isset($data['type']) && !in_array($data['type'], $this->types)) {
               $data['type'] = $type; 
            }
            if(isset($data['state']) && !in_array($data['state'], $this->states)) {
               $data['state'] = $state; 
            }

          $this->CI->db->where('user_id', $user_id);
          $this->CI->db->update('e_scheduler', $data);
          
          $report = array();
          $report['error'] = $this->CI->db->_error_number();
          $report['message'] = $this->CI->db->_error_message();
          return ($report !== 0?true:false);
        }
        catch(Exception $e){
           log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
           return FALSE;
        }                
    }
    
    public function delete($user_id) {
        try {
            $this->CI->db->where('user_id', $user_id);
            $this->CI->db->delete('e_scheduler');
            $this->CI->db->where('user_id', $user_id);
            $this->CI->db->delete('e_user_scheduler');
        }
        catch(Exception $e){
           log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
           return FALSE;
        }         
    }

    public function getDeviceToken($user_id=0){
        try {
            $this->CI->db->select('device_token');
            $this->CI->db->from('e_devices');
            $this->CI->db->where('user_id',$user_id);
            $this->CI->db->limit(1);
            $query = $this->CI->db->get();
            return (isset($query->row()->device_token)?$query->row()->device_token:false);
        }
        catch(Exception $e){
            log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
            return FALSE;
         }
    }
    
}
