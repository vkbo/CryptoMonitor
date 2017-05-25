<?php
   /**
    *  Crypto Monitor – Init File
    * ============================
    *  Created 2017-05-25
    */

    if(!isset($bMain)) exit();

    require_once("config.php");
    require_once("includes/functions.php");

    $oDB = new mysqli($cDBHost,$cDBUser,$cDBPass,$cDBMain);
?>
