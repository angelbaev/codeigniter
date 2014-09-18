<?php
/*
Windows Phone 7
*/

class WindowsPhonePushPriority {
    const TileImmediately = 1; 
    const ToastImmediately = 2;
    const RawImmediately = 3;
    const TileWait450 = 11;
    const ToastWait450 = 12;
    const RawWait450 = 13;
    const TileWait900 = 21;	
    const ToastWait900 = 22;	
    const RawWait900 = 23;
}

/*
Windows Phone 8
*/

class WPNTypesEnum{       
    const TOAST = 'wns/toast';
    const BADGE = 'wns/badge'; 
    const TILE  = 'wns/tile';
    const RAW   = 'wns/raw';
}                         

