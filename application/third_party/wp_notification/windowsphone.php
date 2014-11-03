<?php 
final class WindowsPhonePushDelay {
  const Immediate=0;
  private function __construct(){}
}

class WindowsPhonePushNotification {
  private $notif_url = '';

  public function __construct($notif_url) {
     $this->notif_url = $notif_url;
  }
  public function push($target, $message_id, $message1, $notif_url) {
    // Create the toast message
    $toastMessage = "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
    "<wp:Notification xmlns:wp=\"WPNotification\">" .
    "<wp:Toast>" .
    "<wp:Text1>" . "SendToast" . "</wp:Text1>" .
    "<wp:Text2>" . $message1 . "</wp:Text2>" .
    "<wp:Param>/BestDeal.xaml?=Toast Notification</wp:Param>" .
    "</wp:Toast> " .
    "</wp:Notification>";
        
    // Create request to send
    $r = curl_init();
    curl_setopt($r, CURLOPT_URL,$notif_url);
    curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($r, CURLOPT_POST, true);
    curl_setopt($r, CURLOPT_HEADER, true); 
    
    // add headers
    $httpHeaders=array('Content-type: text/xml; charset=utf-8', 'X-WindowsPhone-Target: toast',
    'Accept: application/*', 'X-NotificationClass: 2','Content-Length:'.strlen($toastMessage));
    curl_setopt($r, CURLOPT_HTTPHEADER, $httpHeaders);
    
    // add message
    curl_setopt($r, CURLOPT_POSTFIELDS, $toastMessage);
    
    // execute request
    $output = curl_exec($r);
    curl_close($r);

  }
}

?>