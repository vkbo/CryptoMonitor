<?php
   /**
    *  Crypto Monitor – Cron Functions
    * =================================
    *  Created 2017-05-20
    */

    if(!isset($bCron)) exit();

    function getTimeStamp() {
        return date("[Y-m-d H:i:s]",time());
    }

    function roundHour($iTime) {
        return strtotime(date("Y-m-d H:00:00",$iTime));
    }

    function roundDay($iTime) {
        return strtotime(date("Y-m-d 00:00:00",$iTime));
    }
?>
