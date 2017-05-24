<?php
   /**
    *  Crypto Monitor – Main Functions
    * =================================
    *  Created 2017-05-21
    */

    function rdblNum($dValue, $iDecimals=2, $sUnit="") {
        $dValue = floatval($dValue);
        return number_format($dValue,$iDecimals,"."," ")." ".$sUnit;
    }

    function rdblBigNum($dValue, $iDecimals=2, $sUnit="") {
        $dValue = floatval($dValue);
        $aUnits = array("","k","M","G","T","P","E");
        $iCount = 0;
        while($dValue >= 1000) {
            $dValue /= 1000;
            $iCount++;
        }
        return number_format($dValue,$iDecimals,"."," ")." ".$aUnits[$iCount].$sUnit;
    }

    function rdblSmallNum($dValue, $iDecimals=2, $sUnit="") {
        $dValue = floatval($dValue);
        $aUnits = array("","m","µ","n","p","f","a");
        $iCount = 0;
        while($dValue <= 1.0) {
            $dValue *= 1000;
            $iCount++;
        }
        return number_format($dValue,$iDecimals,"."," ")." ".$aUnits[$iCount].$sUnit;
    }

    function rdblSeconds($iSec, $bNoZero=true) {
        $iHour = floor($iSec/3600);
        $iMin  = floor($iSec/60 - $iHour*60);
        $iSec  = $iSec - $iMin*60 - $iHour*3600;

        if($bNoZero && $iHour == 0 && $iMin == 0) {
            return sprintf("%02dm %02ds",$iMin,$iSec);
        }

        if($bNoZero && $iHour == 0) {
            return sprintf("%02dm %02ds",$iMin,$iSec);
        }

        return sprintf("%02dh %02dm %02ds",$iHour,$iMin,$iSec);
    }
?>
