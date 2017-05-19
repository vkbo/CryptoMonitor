<?php
   /**
    *  Crypto Monitor – Hourly Stats Script
    * ======================================
    *  Created 2017-05-19
    */

    require_once("config.php");

    $oDB = new mysqli($cDBHost,$cDBUser,$cDBPass,$cDBMain);
    $webOpts = array("http" => array("header" => "User-Agent: Mozilla/5.0"));
    $webContext = stream_context_create($webOpts);

    $oPools = $oDB->query("SELECT * FROM pools WHERE Active = 1");

    function getTimeStamp()    {return date("[Y-m-d H:i:s]",time());};
    function roundHour($iTime) {return strtotime(date("Y-m-d H:00:00",$iTime));};

    while($aPool = $oPools->fetch_assoc()) {

        $SQL = sprintf(
           "SELECT
            w.ID AS ID,
            w.Name AS Name,
            w.Address AS Address
            FROM wallets AS w
            LEFT JOIN pool_wallet AS pw ON w.ID = pw.WalletID
            WHERE pw.PoolID = '%s'",
            $aPool["ID"]
        );
        $oWallet = $oDB->query($SQL);
        while($aWallet = $oWallet->fetch_assoc()) {

            // Calculate Hourly Averages
            echo getTimeStamp()." Calculate hourly averages ";

            $SQL = sprintf(
               "SELECT TimeStamp
                FROM mining_hourly
                WHERE WalletID = '%s' AND PoolID = '%s'
                ORDER BY TimeStamp DESC
                LIMIT 0, 1",
                $aWallet["ID"],
                $aPool["ID"]
            );
            $oHourly = $oDB->query($SQL);
            if($oHourly->num_rows > 0) {
                $aTemp = $oHourly->fetch_assoc();
                $iLastHour = strtotime($aTemp["TimeStamp"])+3600;
            } else {
                $iLastHour = roundHour(time()-7*86400);
            }
            echo "since ".date("Y-m-d H:i:s",$iLastHour)."\n";
            $iCurrHour = roundHour(time());
            for($iStart = $iLastHour; $iStart < $iCurrHour; $iStart += 3600) {
                echo getTimeStamp()." Averaging for ".date("Y-m-d H:i:s",$iStart)."\n";
                $SQL = sprintf(
                   "SELECT
                    MAX(Hashes) AS Hashes,
                    MAX(Balance) AS Balance,
                    AVG(HashRate) AS HashRate
                    FROM mining
                    WHERE WalletID = '%s' AND PoolID = '%s'
                    AND TimeStamp >= '%s' AND TimeStamp < '%s'
                    HAVING MAX(Hashes) IS NOT NULL",
                    $aWallet["ID"],
                    $aPool["ID"],
                    date("Y-m-d-H-i-s",$iStart),
                    date("Y-m-d-H-i-s",$iStart+3600)
                );
                $oHourly = $oDB->query($SQL);
                if($oHourly->num_rows > 0) {
                    $aHourly = $oHourly->fetch_assoc();
                    $SQL  = "INSERT INTO mining_hourly (TimeStamp,WalletID,PoolID,Hashes,Balance,HashRate) VALUES (";
                    $SQL .= "'".date("Y-m-d-H-i-s",$iStart)."',";
                    $SQL .= "'".$aWallet["ID"]."',";
                    $SQL .= "'".$aPool["ID"]."',";
                    $SQL .= "'".$aHourly["Hashes"]."',";
                    $SQL .= "'".$aHourly["Balance"]."',";
                    $SQL .= "'".$aHourly["HashRate"]."')";
                    $oDB->query($SQL);
                }

            }
        }
    }
?>
