<?php
   /**
    *  Crypto Monitor – Daily Stats Script
    * =====================================
    *  Created 2017-05-19
    */

    if(!isset($bCron)) exit();

    function calcDailyWalletStats($oDB, $aPool, $bSave=true) {

        $sPoolName = $aPool["Name"];
        $iPoolID   = $aPool["ID"];

        $SQL  = "SELECT ";
        $SQL .= "w.ID AS ID, ";
        $SQL .= "w.Name AS Name, ";
        $SQL .= "w.Address AS Address ";
        $SQL .= "FROM wallets AS w ";
        $SQL .= "LEFT JOIN pool_wallet AS pw ON w.ID = pw.WalletID ";
        $SQL .= "WHERE pw.PoolID = '".$iPoolID."'";
        $oWallets = $oDB->query($SQL);

        while($aWallet = $oWallets->fetch_assoc()) {

            echo getTimeStamp()." Calculating daily wallet averages for ".$aWallet["Name"]." on ".$sPoolName."\n";

            $SQL  = "SELECT TimeStamp ";
            $SQL .= "FROM mining_daily ";
            $SQL .= "WHERE WalletID = '".$aWallet["ID"]."' ";
            $SQL .= "AND PoolID = '".$iPoolID."' ";
            $SQL .= "ORDER BY TimeStamp DESC ";
            $SQL .= "LIMIT 0, 1";
            $oDaily = $oDB->query($SQL);

            if($oDaily->num_rows > 0) {
                $aTemp = $oDaily->fetch_assoc();
                $iLastDay = strtotime($aTemp["TimeStamp"])+86400;
            } else {
                $iLastDay = roundDay(time()-7*86400);
            }
            $oDaily->close();

            $iCurrDay = roundDay(time());
            for($iStart = $iLastDay; $iStart < $iCurrDay; $iStart += 86400) {

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
                $SQL .= "AND TimeStamp < '".date("Y-m-d-H-i-s",$iStart+86400)."' ";
                $SQL .= "HAVING MAX(Hashes) IS NOT NULL";
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
                    if($bSave) $oDB->query($SQL);
                }
                $oDaily->close();
            }
        }
        $oWallets->close();

        return;
    }

    function calcDailyPoolStats($oDB, $aPool, $bSave=true) {

        $sPoolName = $aPool["Name"];
        $iPoolID   = $aPool["ID"];

        echo getTimeStamp()." Calculating daily pool averages for ".$sPoolName."\n";

        $SQL  = "SELECT TimeStamp ";
        $SQL .= "FROM pool_daily ";
        $SQL .= "WHERE PoolID = '".$iPoolID."' ";
        $SQL .= "ORDER BY TimeStamp DESC ";
        $SQL .= "LIMIT 0, 1";
        $oDaily = $oDB->query($SQL);

        if($oDaily->num_rows > 0) {
            $aTemp = $oDaily->fetch_assoc();
            $iLastDay = strtotime($aTemp["TimeStamp"])+86400;
        } else {
            $iLastDay = roundDay(time()-7*86400);
        }
        $oDaily->close();

        $iCurrDay = roundDay(time());
        for($iStart = $iLastDay; $iStart < $iCurrDay; $iStart += 86400) {

            echo getTimeStamp()." Averaging for ".date("Y-m-d H:i",$iStart)."\n";
            $SQL  = "SELECT ";
            $SQL .= "COUNT(ID) AS Count, ";
            $SQL .= "ROUND(AVG(Difficulty)) AS AvgDifficulty, ";
            $SQL .= "ROUND(AVG(Luck)) AS AvgLuck, ";
            $SQL .= "ROUND(AVG(Reward)) AS AvgReward, ";
            $SQL .= "SUM(Reward) AS SumReward ";
            $SQL .= "FROM pool_blocks ";
            $SQL .= "WHERE PoolID = '".$iPoolID."' ";
            $SQL .= "AND FoundTime >= '".date("Y-m-d-H-i-s",$iStart)."' ";
            $SQL .= "AND FoundTime < '".date("Y-m-d-H-i-s",$iStart+86400)."' ";
            $SQL .= "HAVING AVG(Difficulty) IS NOT NULL";
            $oBlocks = $oDB->query($SQL);

            $SQL  = "SELECT ";
            $SQL .= "COUNT(ID) AS Entries, ";
            $SQL .= "AVG(HashRate) AS HashRate, ";
            $SQL .= "ROUND(AVG(Miners)) AS Miners ";
            $SQL .= "FROM pool_meta ";
            $SQL .= "WHERE PoolID = '".$iPoolID."' ";
            $SQL .= "AND TimeStamp >= '".date("Y-m-d-H-i-s",$iStart)."' ";
            $SQL .= "AND TimeStamp < '".date("Y-m-d-H-i-s",$iStart+86400)."' ";
            $SQL .= "HAVING AVG(HashRate) IS NOT NULL";
            $oMeta = $oDB->query($SQL);

            if($oBlocks->num_rows > 0) {

                $aBlocks = $oBlocks->fetch_assoc();
                $SQL  = "INSERT INTO pool_daily (";
                $SQL .= "TimeStamp, PoolID, AvgDifficulty, AvgLuck, AvgReward, ";
                $SQL .= "SumReward, Blocks, HashRate, Miners, MetaEntries";
                $SQL .= ") VALUES (";
                $SQL .= "'".date("Y-m-d-H-i-s",$iStart)."',";
                $SQL .= "'".$iPoolID."',";
                $SQL .= "'".$aBlocks["AvgDifficulty"]."',";
                $SQL .= "'".$aBlocks["AvgLuck"]."',";
                $SQL .= "'".$aBlocks["AvgReward"]."',";
                $SQL .= "'".$aBlocks["SumReward"]."',";
                $SQL .= "'".$aBlocks["Count"]."',";
                if($oMeta->num_rows > 0) {
                    $aMeta = $oMeta->fetch_assoc();
                    $SQL .= "'".$aMeta["HashRate"]."',";
                    $SQL .= "'".$aMeta["Miners"]."',";
                    $SQL .= "'".$aMeta["Entries"]."')";
                } else {
                    $SQL .= "'0.0',";
                    $SQL .= "'0',";
                    $SQL .= "'0')";
                }
                if($bSave) $oDB->query($SQL);
            }
            $oBlocks->close();
            $oMeta->close();
        }

        return;
    }
?>
