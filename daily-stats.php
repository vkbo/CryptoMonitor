<?php
   /**
    *  Crypto Monitor – Daily Stats Script
    * =====================================
    *  Created 2017-05-19
    */

    require_once("config.php");

    $oDB = new mysqli($cDBHost,$cDBUser,$cDBPass,$cDBMain);
    $webOpts = array("http" => array("header" => "User-Agent: Mozilla/5.0"));
    $webContext = stream_context_create($webOpts);

    $oPools = $oDB->query("SELECT * FROM pools WHERE Active = 1");

    function getTimeStamp()   {return date("[Y-m-d H:i:s]",time());};
    function roundDay($iTime) {return strtotime(date("Y-m-d 00:00:00",$iTime));};

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
            echo getTimeStamp()." Calculating daily averages ";

            $SQL = sprintf(
               "SELECT TimeStamp
                FROM mining_daily
                WHERE WalletID = '%s' AND PoolID = '%s'
                ORDER BY TimeStamp DESC
                LIMIT 0, 1",
                $aWallet["ID"],
                $aPool["ID"]
            );
            $oDaily = $oDB->query($SQL);
            if($oDaily->num_rows > 0) {
                $aTemp = $oDaily->fetch_assoc();
                $iLastDay = strtotime($aTemp["TimeStamp"])+86400;
            } else {
                $iLastDay = roundDay(time()-7*86400);
            }
            echo "since ".date("Y-m-d H:i:s",$iLastDay)."\n";
            $iCurrDay = roundDay(time());
            for($iStart = $iLastDay; $iStart < $iCurrDay; $iStart += 86400) {
                echo getTimeStamp()." Averaging for ".date("Y-m-d H:i:s",$iStart)."\n";
                $SQL = sprintf(
                   "SELECT
                    COUNT(ID) AS Entries,
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
                    date("Y-m-d-H-i-s",$iStart+86400)
                );
                $oDaily = $oDB->query($SQL);
                if($oDaily->num_rows > 0) {
                    $aDaily = $oDaily->fetch_assoc();
                    $SQL  = "INSERT INTO mining_daily (TimeStamp,WalletID,PoolID,Hashes,Balance,HashRate,Entries) VALUES (";
                    $SQL .= "'".date("Y-m-d-H-i-s",$iStart)."',";
                    $SQL .= "'".$aWallet["ID"]."',";
                    $SQL .= "'".$aPool["ID"]."',";
                    $SQL .= "'".$aDaily["Hashes"]."',";
                    $SQL .= "'".$aDaily["Balance"]."',";
                    $SQL .= "'".$aDaily["HashRate"]."',";
                    $SQL .= "'".$aDaily["Entries"]."')";
                    $oDB->query($SQL);
                }

            }
        }
    }
?>
