<?php
   /**
    *  Crypto Monitor – Main Functions
    * =================================
    *  Created 2017-05-21
    */

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
?>
