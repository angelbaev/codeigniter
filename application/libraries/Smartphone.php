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
 * PHP Smartphone class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Validation
 * @author		Angel Baev
 * @link		
 */
define('SMARTPHONE_ANDROID', 'ANDROID');
define('SMARTPHONE_APPLE', 'APPLE');
define('SMARTPHONE_WINDOW_PHONE_7', 'WPN7');
define('SMARTPHONE_WINDOW_PHONE_8', 'WPN8');

class CI_Smartphone {
    const ANDROID_API_KEY = 'AIzaSyA-t2mzPMEdLza8nqYn5vbkcH7XV5NmfYo';
    const ANDROID_SERVER_URL = 'https://android.googleapis.com/gcm/send';
//    const APPLE_SOCKET_URL = 'ssl://gateway.sandbox.push.apple.com:2195';
    const APPLE_SOCKET_URL = 'ssl://gateway.push.apple.com:2195';
    const APPLE_PASSWORD_PHRASE = 'Auth123';//KDPass@123
    const WP_SECRET_KEY = '';

    protected $CI;


    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->database();
        
        log_message('debug', "CI_Smartphone Initialized");
    }
    
    private function getClientIP() {
        $ip_address = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'])
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'])
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'])
            $ip_address = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'])
            $ip_address = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'])
            $ip_address = $_SERVER['HTTP_FORWARDED'];
        else if($_SERVER['REMOTE_ADDR'])
            $ip_address = $_SERVER['REMOTE_ADDR'];
        else
            $ip_address = 'UNKNOWN';
 
        
        return (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false?$ip_address:'');
    }
    
    
    private function getClientLocation() {
        $json = file_get_contents('http://freegeoip.net/json/'.$this->getClientIP());
        $data = json_decode($json);
        //print "<pre>"; print_r($data); print "</pre>";
        return $data;
    }
    
    private function sendAndroidNotification($registrationId, $pushType, $message) {
        $loc = $this->getClientLocation();
        $ip = (isset($loc->ip)?$loc->ip:'');
        $location = (isset($loc->city)?$loc->city:'').', '.(isset($loc->country_nam)?$loc->country_nam:'');
        $date = date('d F Y');
        $time = date('H:i');
        
        $headers = array("Content-Type:" . "application/json", "Authorization:" . "key=" . self::ANDROID_API_KEY);
        $params = array(
                  'requesttype' => $pushType
                  , 'ip' => $ip
                  , 'location' => $location
                  , "date" => $date
                  , 'time' => $time
                  , 'message' => $message
                  , 'vibrate' => 1
                  , 'sound' => 1
                  , 'largeIcon' => 'large_icon'
                  , 'smallIcon' => 'small_icon'
                );
        $data = array(
                'registration_ids' => array($registrationId),
                'data' => $params
        );
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_URL, self::ANDROID_SERVER_URL );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($data) );

        $response = curl_exec($ch);
        curl_close($ch);
        
        $user_id = $this->getUserIdByToken(base64_encode($registrationId));
        if ($user_id) {
            $info = $this->getSchedulerInfo($user_id);
            
            $json['requesttype'] = $info->requesttype;
            $json['requestdescription'] = $info->info;
            $data = array();
            $data['ip'] = (isset($info->ip)?$info->ip:'');
            $data['location'] = (isset($info->location)?$info->location:'');
            $data['time'] = $info->time;
            //$data['date'] = (isset($info->date)?$info->date:'');
            //$data['time'] = (isset($info->time)?$info->time:'');

            $json['data'] = $data;
            $json['andorid_response'] = $response;
            echo json_encode($json);
            
        } else {
            echo $response;
        }
     }

    private function sendAppleNotification($registrationId, $pushType, $message) {
      $cert_path = realpath(APPPATH.'../cert').DIRECTORY_SEPARATOR; 
//      $cert_path .= 'push_dev_all.pem';
      $cert_path .= 'production_apns.pem';
//      $cert_path .= 'push_dev_all.p12';
      if(file_exists($cert_path) && is_file($cert_path) && is_readable($cert_path)) {
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $cert_path);
        stream_context_set_option($ctx, 'ssl', 'passphrase', self::APPLE_PASSWORD_PHRASE);
        $fp = stream_socket_client(self::APPLE_SOCKET_URL, $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
        if (!$fp) {
            exit("Failed to connect: $err $errstr" . PHP_EOL);
        }
        
        $loc = $this->getClientLocation();
        $ip = (isset($loc->ip)?$loc->ip:'');
        $location = (isset($loc->city)?$loc->city:'').', '.(isset($loc->country_nam)?$loc->country_nam:'');
        $date = date('d F Y');
        $time = date('H:i');
        
        $data['aps'] = array(
                'alert' => $message,
                'sound' => 'default'
                );

        $data['requesttype'] = $pushType;
        $data['data'] = array(
                'ip' => $ip,
                'location' => $location,
                'date' => $date,
                'time' => $time
                );
        
       $json = json_encode($data); 
       // Build the binary notification
       @$msg = chr(0) . pack('n', 32) . pack('H*', $registrationId) . pack('n', strlen($json)) . $json;
       
       // Send it to the server
       $result = fwrite($fp, $msg, strlen($msg));
       $res = array();
       if (!$result) {
          $res['error'] = 'Message not delivered.'; 
       } else {
          //$res['success'] = 'Message successfully delivered.'; 
        $user_id = $this->getUserIdByToken(base64_encode($registrationId));
        if ($user_id) {
            $info = $this->getSchedulerInfo($user_id);
            
            $res['requesttype'] = $info->requesttype;
            $res['requestdescription'] = $info->info;
            $data = array();
            $data['ip'] = (isset($info->ip)?$info->ip:'');
            $data['location'] = (isset($info->location)?$info->location:'');
            $data['time'] = $info->time;
            $data['success'] = 'Message successfully delivered.';
            //$data['date'] = (isset($info->date)?$info->date:'');
            //$data['time'] = (isset($info->time)?$info->time:'');

            $res['data'] = $data;
      }           
       }
       echo json_encode($res);
       return ;
      }
       echo json_encode(array('error' => 'Invalid certificate!'));
    }

    private function sendWindowPhone_7_Notification($registrationId, $pushType, $message) {
        include(APPPATH.'third_party/wp_notification/WindowsPhonePushNotification.php');
        try {
            $uri = $registrationId;

            $notifier = new WindowsPhonePushNotification();
            $result = $notifier->pushToast($uri, $pushType, $message);

            $user_id = $this->getUserIdByToken(base64_encode($registrationId));
            if ($user_id) {
                $info = $this->getSchedulerInfo($user_id);

                $json['requesttype'] = $info->requesttype;
                $json['requestdescription'] = $info->info;
                $data = array();
                $data['ip'] = (isset($info->ip)?$info->ip:'');
                $data['location'] = (isset($info->location)?$info->location:'');
                $data['time'] = $info->time;
                //$data['date'] = (isset($info->date)?$info->date:'');
                //$data['time'] = (isset($info->time)?$info->time:'');

                $json['data'] = $data;
                $json['wpn_response'] = $result;
                echo json_encode($json);

            } else {
               echo json_encode($result); 
            }
            
        } catch (Exception $e) {
            //var_dump($e);
             print "<pre>"; print_r($e); print "</pre>";
        }        

        /*
        include(APPPATH.'third_party/wp_notification/app.php');
        $client = new WindowsPhonePushClient7($registrationId);
        $result = $client->send_raw_update($message);
        
        echo $result;
        */
        /*
         * 
        include(APPPATH.'third_party/wp_notification/windowsphone.php');
        $client = new WindowsPhonePushNotification($registrationId);
        $target = $message_id = NULL;
        $user_id = $this->getUserIdByToken(base64_encode($registrationId));
        if ($user_id) {
            $info = $this->getSchedulerInfo($user_id);
        }
        $message = (isset($info->info)?$info->info:'Empty message!');
        $result = $client->push($target, $message_id, $message, $registrationId);
        print "<pre>"; print_r($result); print "</pre>";
        */
        
/*
$xml = simplexml_load_string($xml_string);
$json = json_encode($xml);
$array = json_decode($json,TRUE);


class XmlToJson {
    public function Parse ($url) {
        $fileContents= file_get_contents($url);
        $fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
        $fileContents = trim(str_replace('"', "'", $fileContents));
        $simpleXml = simplexml_load_string($fileContents);
        $json = json_encode($simpleXml);

        return $json;
    }
}

 */
    }
    
    private function sendWindowPhone_8_Notification($registrationId, $pushType, $message) {
        include(APPPATH.'third_party/wp_notification/app.php');

        $client = new WindowsPhonePushClient8($registrationId, self::WP_SECRET_KEY);

        $result = $client->post_tile('', $client->build_tile_xml($message, ''));
        echo $result;
    }
    
    public function sendPush($registrationId, $pushType, $message, $device_type = SMARTPHONE_ANDROID) {
        
        switch($device_type) {
            case SMARTPHONE_APPLE:
                $this->sendAppleNotification($registrationId, $pushType, $message);
                break;
            case SMARTPHONE_WINDOW_PHONE_7:
                $this->sendWindowPhone_7_Notification($registrationId, $pushType, $message);
                break;
            case SMARTPHONE_WINDOW_PHONE_8:
                $this->sendWindowPhone_8_Notification($registrationId, $pushType, $message);
                break;
            case SMARTPHONE_ANDROID:
            default:
                $this->sendAndroidNotification($registrationId, $pushType, $message);
                break;
        }
    }
    
    public function getDeviceTypes() {
      return array(SMARTPHONE_ANDROID, SMARTPHONE_APPLE, SMARTPHONE_WINDOW_PHONE_7, SMARTPHONE_WINDOW_PHONE_8);  
    }
    
    private function getSchedulerInfo($user_id) {
        try {
            $this->CI->db->select('u.login_name, u.fname, u.lname, s.command, s.requesttype, s.info, s.start_time');
            $this->CI->db->from('e_users as u');
            $this->CI->db->join('e_scheduler as s', 's.user_id = u.id', 'LEFT');
            $this->CI->db->where('u.id', $user_id);
            $this->CI->db->limit(1);

            $query = $this->CI->db->get();
            $result = $query->row();
            if (empty($result->requesttype)) {
             $result->requesttype = 'none';   
            }
            if (empty($result->info)) {
              $result->info = '';  
            }
            
            $this->CI->db->select('*');
            $this->CI->db->from('e_users_ip');
            $this->CI->db->where('user_id', $user_id);
            $this->CI->db->order_by('id', 'DESC');
            $this->CI->db->limit(1);
            $query = $this->CI->db->get();
            if(isset($query->row()->id)) {
              $result->ip = $query->row()->ip;  
              $result->location = $query->row()->location;
              /*
              $result->date = $query->row()->date;  
              $result->time = $query->row()->time;  
              */
              if ($result->requesttype != 'none') {
                 $result->time = $query->row()->date.' '.$query->row()->time.':00';   
              } else {
                 $result->time = date('Y-m-d H:i:s'); 
              }
              
            }
            
            
            if(!isset($result->ip)) {
                $result->ip = '';  
            }
            if(!isset($result->location)) {
                $result->location = '';  
            }
            if(!isset($result->time)) {
                $result->time = date('Y-m-d H:i:s');  
            }
            
            return $result;
        }
        catch(Exception $e){
           log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
           return FALSE;
        }                 
        
    }

    private function getUserIdByToken($device_token) {
        try {
            $this->CI->db->select('user_id');
            $this->CI->db->from('e_devices');
            $this->CI->db->where('device_token', $device_token);
            $this->CI->db->limit(1);

            $query = $this->CI->db->get();
            return (isset($query->row()->user_id)?$query->row()->user_id:false);
        }
        catch(Exception $e){
           log_message('error',$e->getMessage().' in '.$e->getFile() .':'.$e->getLine());
           return FALSE;
        }                 
    }
    

}
