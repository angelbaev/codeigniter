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
    const APPLE_SOCKET_URL = 'ssl://gateway.sandbox.push.apple.com:2195';
    const APPLE_PASSWORD_PHRASE = 'KDPass@123';
    const WP_SECRET_KEY = '';

    protected $CI;


    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct() {
        $this->CI = & get_instance();

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

        echo $response;
     }

    private function sendAppleNotification($registrationId, $pushType, $message) {
      $cert_path = realpath(APPPATH.'../cert').DIRECTORY_SEPARATOR; 
      $cert_path .= 'push_dev_all.pem';
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
          $res['success'] = 'Message successfully delivered.'; 
       }
       echo json_encode($res);
       return ;
      }
       echo json_encode(array('error' => 'Invalid certificate!'));
    }

    private function sendWindowPhone_7_Notification($registrationId, $pushType, $message) {
        include(APPPATH.'third_party/wp_notification/app.php');
        $client = new WindowsPhonePushClient7($registrationId);
        $result = $client->send_raw_update($message);
        echo $result;
        
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

}
