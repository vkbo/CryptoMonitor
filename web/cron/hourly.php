<?php
   /**
    *  Crypto Monitor – Hourly Stats Script
    * ======================================
    *  Created 2017-05-19
    */

    if(!isset($bCron)) exit();

    function calcHourlyWalletStats($oDB, $aPool, $bSave=true) {

        $sPoolName = $aPool["Name"];
        $iPoolID   = $aPool["ID"];

        $SQL  = "SELECT ";
        $SQL .= "w.ID AS ID, ";
        $SQL .= "w.Name AS Name, ";
        $SQL .= "w.Address AS Address ";
        $SQL .= "FROM wallets AS w ";
        $SQL .= "LEFT JOIN pool_wallet AS pw ON w.ID = pw.WalletID ";
        $SQL .= "WHERE pw.PoolID = '".$iPoolID."' ";
        $SQL .= "AND Active = 1";
        $oWallets = $oDB->query($SQL);

        while($aWallet = $oWallets->fetch_assoc()) {

            echo getTimeStamp()." Calculating hourly wallet averages for ".$aWallet["Name"]." on ".$sPoolName."\n";

            $SQL  = "SELECT Hour ";
            $SQL .= "FROM mining_hourly ";
            $SQL .= "WHERE WalletID = '".$aWallet["ID"]."' ";
            $SQL .= "AND PoolID = '".$iPoolID."' ";
            $SQL .= "ORDER BY Hour DESC ";
            $SQL .= "LIMIT 0, 1";
            $oHourly = $oDB->query($SQL);

            if($oHourly->num_rows > 0) {
                $aTemp = $oHourly->fetch_assoc();
                $iLastHour = strtotime($aTemp["Hour"])+3600;
            } else {
                $iLastHour = roundHour(time()-7*86400);
            }
            $oHourly->close();

            $iCurrHour = roundHour(time());
            for($iStart = $iLastHour; $iStart < $iCurrHour; $iStart += 3600) {

                echo getTimeStamp()." Averaging for ".date("Y-m-d H:i",$iStart)."\n";
                $SQL  = "SELECT ";
                $SQL .= "COUNT(ID) AS Entries, ";
                $SQL .= "MAX(Hashes) AS Hashes, ";
                $SQL .= "MAX(Balance) AS Balance, ";
                $SQL .= "AVG(HashRate) AS HashRate ";
                $SQL .= "FROM mining ";
                $SQL .= "WHERE WalletID = '".$aWallet["ID"]."' ";
                $SQL .= "AND PoolID = '".$iPoolID."' ";
                $SQL .= "AND TimeStamp >= '".date("Y-m-d-H-i-s",$iStart)."' ";
                $SQL .= "AND TimeStamp < '".date("Y-m-d-H-i-s",$iStart+3600)."' ";
                $SQL .= "HAVING MAX(Hashes) IS NOT NULL";
                $oHourly = $oDB->query($SQL);

                if($oHourly->num_rows > 0) {

                    $aHourly = $oHourly->fetch_assoc();
                    $SQL  = "INSERT INTO mining_hourly (";
                    $SQL .= "Hour, ";
                    $SQL .= "WalletID, ";
                    $SQL .= "PoolID, ";
                    $SQL .= "Hashes, ";
                    $SQL .= "Balance, ";
                    $SQL .= "HashRate, ";
                    $SQL .= "Entries";
                    $SQL .= ") VALUES (";
                    $SQL .= "'".date("Y-m-d-H-i-s",$iStart)."',";
                    $SQL .= "'".$aWallet["ID"]."',";
                    $SQL .= "'".$aPool["ID"]."',";
                    $SQL .= "'".$aHourly["Hashes"]."',";
                    $SQL .= "'".$aHourly["Balance"]."',";
                    $SQL .= "'".$aHourly["HashRate"]."',";
                    $SQL .= "'".$aHourly["Entries"]."')";
                    if($bSave) $oDB->query($SQL);
                }
                $oHourly->close();
            }
        }
        $oWallets->close();

        return;
    }
?>
