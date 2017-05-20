<?php
   /**
    *  Crypto Monitor – Index File
    * =============================
    *  Created 2017-05-17
    */

    require_once("config.php");

    $oDB = new mysqli($cDBHost,$cDBUser,$cDBPass,$cDBMain);

?><!DOCTYPE html>

<html>
    <link rel="stylesheet" href="css/normalize.css" type="text/css" media="all">
    <link rel="stylesheet" href="css/styles.css" type="text/css" media="all">
<head>

</head>

<body>
    Hello Kitty!<br />
    <canvas id="plotCanvas" width="1000" height="500" style="border:1px solid #000000;"></canvas>
    <script src="js/stairs.js"></script>
    <?php
        $SQL = "SELECT * FROM mining_hourly ORDER BY TimeStamp DESC LIMIT 0, 40";
        $oPlot = $oDB->query($SQL);
        $aHashRates = array();
        $iN = 0;
        while($aPlot = $oPlot->fetch_assoc()) {
            $dHashRate = floatval($aPlot["HashRate"]);
            $aHashRates[strtotime($aPlot["TimeStamp"])] = $dHashRate;
            if($iN == 0) {
                $dMin = $dHashRate;
                $dMax = $dHashRate;
            } else {
                $dMin = ($dHashRate < $dMin ? $dHashRate : $dMin);
                $dMax = ($dHashRate > $dMax ? $dHashRate : $dMax);
            }
            $iN++;
            //echo number_format($dHashRate,3)." Min: ".number_format($dMin,3)." Max: ".number_format($dMax,3)."<br>";
        }
        $iN = 0;
        $iCount = count($aHashRates);
        $dRange = $dMax-$dMin;
        foreach($aHashRates as $iTime => $dHash) {
            $dX = $iN/$iCount;
            $dY = ($dHash-$dMin)/$dRange;
            $iX = floor(900*$dX);
            $iY = floor(400*$dY);
            //echo "<div style='position: absolute; left: ".(30+$iX)."px; bottom: ".$iY."px; width: ".floor(900/$iCount)."px; height: 5px; background: #0000ff;'></div>";
            $iN++;
        }
    ?>
    <script>stairs([0,1,2,3,4,5,6,7,8,9,10],[4,5,6,4,5,3,6,2,4,5],1000,500,"plotCanvas");</script>
</body>

</html>
