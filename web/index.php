<?php
   /**
    *  Crypto Monitor – Index File
    * =============================
    *  Created 2017-05-17
    */

    $bMain = true;

    require_once("config.php");

    $oDB = new mysqli($cDBHost,$cDBUser,$cDBPass,$cDBMain);

    $SQL = "SELECT * FROM mining_hourly WHERE PoolID = 2 AND WalletID = 1 ORDER BY Hour DESC Limit 0,102";
    $oData = $oDB->query($SQL);
    $aData = array();
    while($aTemp = $oData->fetch_assoc()) {
        $aData[] = array(
            "Time" => strtotime($aTemp["Hour"]),
            "Hash" => array(intval($aTemp["Hashes"]),null,null),
            "Coin" => array(intval($aTemp["Balance"]),null,null)
        );
    }
    $oData->free();
    $nData = count($aData);

    // Calculate 1st derivative
    for($i=0; $i<$nData-1; $i++) {
        $aData[$i]["Hash"][1] = $aData[$i]["Hash"][0] - $aData[$i+1]["Hash"][0];
        $aData[$i]["Coin"][1] = $aData[$i]["Coin"][0] - $aData[$i+1]["Coin"][0];
    }

    // Calculate 2nd derivative
    for($i=0; $i<$nData-2; $i++) {
        $aData[$i]["Hash"][2] = $aData[$i]["Hash"][1] - $aData[$i+1]["Hash"][1];
        $aData[$i]["Coin"][2] = $aData[$i]["Coin"][1] - $aData[$i+1]["Coin"][1];
    }

    // Discard the last two array elements
    unset($aData[$nData-1]);
    unset($aData[$nData-2]);
    $nData = count($aData);

    // echo $nData."<br>";
    // print_r($aData);

?><!DOCTYPE html>

<html>

<head>
    <link rel="stylesheet" href="css/normalize.css" type="text/css" media="all">
    <link rel="stylesheet" href="css/styles.css" type="text/css" media="all">
    <link href="https://fonts.googleapis.com/css?family=Source+Code+Pro" rel="stylesheet">
</head>

<body>
    <h1>Crypto Monitor</h1>
    <hr>
    <script src="js/plotEarnings.js"></script>
    <canvas id="plotCanvas" width="1000" height="500" style="border:1px solid #000000;"></canvas>
    <?php
        $sAxis = "]";
        $sHash = "]";
        $sCoin = "]";
        for($i=0; $i<$nData; $i++) {
            $sAxis = $aData[$i]["Time"].($i==0?"":",").$sAxis;
            $sHash = $aData[$i]["Hash"][1].($i==0?"":",").$sHash;
            $sCoin = $aData[$i]["Coin"][1].($i==0?"":",").$sCoin;
        }
        $sAxis = "[".$sAxis;
        $sHash = "[".$sHash;
        $sCoin = "[".$sCoin;

        echo "<script>plotEarnings(".$sAxis.",".$sHash.",".$sCoin.",'plotCanvas');</script>\n"
    ?>
    <hr>
    <?php
        $SQL = "SELECT * FROM mining_hourly WHERE PoolID = 2 AND WalletID = 1 ORDER BY Hour DESC Limit 0,22";
        $oHourly = $oDB->query($SQL);
        $aHourly = array();
        while($aTemp = $oHourly->fetch_assoc()) {
            $aHourly[] = $aTemp;
        }
        $oHourly->free();
        $nHours = count($aHourly);

        $aEarnings = array();
        for($i=$nHours-1; $i>=0; $i--) {
            $currBalance = intval($aHourly[$i]["Balance"]);
            $prevBalance = intval($aHourly[$i+1]["Balance"]);
            $aEarnings[$i][0] = $currBalance-$prevBalance;
            if($i==$nHours-1) {
                $aEarnings[$i][1] = 0;
            } else {
                $minEarn = (isset($minEarn)?(
                    $minEarn<$aEarnings[$i][0]?$aEarnings[$i][0]:$minEarn
                ):$aEarnings[$i][0]);
                $maxEarn = (isset($maxEarn)?(
                    $maxEarn>$aEarnings[$i][0]?$aEarnings[$i][0]:$maxEarn
                ):$aEarnings[$i][0]);
                $aEarnings[$i][1] = $aEarnings[$i][0]-$aEarnings[$i+1][0];
            }
        }

        echo "Min: ".$minEarn."<br>";
        echo "Max: ".$maxEarn."<br>";

        echo "<table class='latest-earnings'>\n";
        for($i=0; $i<$nHours-2; $i++) {

            $earnedVal = $aEarnings[$i][0];
            $changeVal = $aEarnings[$i][1];

            $currHour   = date("d/m H:i",strtotime($aHourly[$i]["Hour"]));
            $currEarned = number_format($earnedVal/1000000000,3);
            $diffEarned = number_format($changeVal/1000000000,3);

            echo "<tr>\n";
                echo "<td class='values'>".$currHour."</td>\n";
                echo "<td style='width: 200px; border: 1px solid #aaaaaa; position: relative;'>";
                    echo "Fig";
                echo "</td>\n";
                echo "<td class='values'>".$currEarned." mXMR</td>\n";
                if($changeVal >= 0) {
                    echo "<td class='values right green'>+".$diffEarned."</td>\n";
                } else {
                    echo "<td class='values right red'>".$diffEarned."</td>\n";
                }
            echo "<tr>\n";
        }
        echo "</table>\n";
    ?>
</body>

</html>
