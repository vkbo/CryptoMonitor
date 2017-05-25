<?php
   /**
    *  Crypto Monitor – Index File
    * =============================
    *  Created 2017-05-17
    */

    $bMain = true;
    require_once("includes/init.php");

   /**
    *  Collect Data
    */

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

   /**
    *  Page Content
    */

    // Page Header
    require_once("includes/header.php");

    $SQL  = "SELECT ";
    $SQL .= "p.ID AS PoolID, ";
    $SQL .= "p.Name AS PoolName, ";
    $SQL .= "p.URL AS PoolURL, ";
    $SQL .= "c.Name AS CurrName, ";
    $SQL .= "c.ISO AS CurrISO, ";
    $SQL .= "c.ValueUnit AS CurrUnit, ";
    $SQL .= "c.DisplayName AS CurrDispName, ";
    $SQL .= "c.DisplayUnit AS CurrDispUnit, ";
    $SQL .= "pm.TimeStamp AS MetaTimeStamp, ";
    $SQL .= "pm.HashRate AS MetaHashRate, ";
    $SQL .= "pm.Miners AS MetaMiners, ";
    $SQL .= "pm.PendingBlocks AS MetaPending, ";
    $SQL .= "pm.LastBlock AS MetaLastBlock, ";
    $SQL .= "pb.BlockCount + pm.PendingBlocks AS BlockCount, ";
    $SQL .= "pb.BlockOrphaned AS BlockOrphaned, ";
    $SQL .= "pb.BlockLuck AS BlockLuck, ";
    $SQL .= "pb.BlockReward AS BlockReward, ";
    $SQL .= "pb.BlockDiff AS BlockDiff ";
    $SQL .= "FROM cryptomonitor.pools AS p ";
    $SQL .= "JOIN cryptomonitor.currency AS c ON c.ID = p.CurrencyID ";
    $SQL .= "JOIN (";
    $SQL .=     "SELECT PoolID, ";
    $SQL .=     "MAX(TimeStamp) AS LatestMeta ";
    $SQL .=     "FROM cryptomonitor.pool_meta ";
    $SQL .=     "GROUP BY PoolID";
    $SQL .= ") AS t1 ON t1.PoolID = p.ID ";
    $SQL .= "JOIN cryptomonitor.pool_meta AS pm ON pm.TimeStamp = t1.LatestMeta AND pm.PoolID = p.ID ";
    $SQL .= "JOIN (";
    $SQL .=     "SELECT PoolID, ";
    $SQL .=     "COUNT(ID) AS BlockCount, ";
    $SQL .=     "SUM(Orphaned) AS BlockOrphaned, ";
    $SQL .=     "AVG(Luck) AS BlockLuck, ";
    $SQL .=     "AVG(Reward) AS BlockReward, ";
    $SQL .=     "AVG(Difficulty) AS BlockDiff ";
    $SQL .=     "FROM pool_blocks ";
    $SQL .=     "WHERE FoundTime > '".date("Y-m-d-H-i-s",time()-86400)."' ";
    $SQL .=     "GROUP BY PoolID";
    $SQL .= ") AS pb ON pb.PoolID = p.ID ";
    $SQL .= "WHERE p.Active = 1 ";
    $SQL .= "ORDER BY pm.TimeStamp DESC";
    $oPools = $oDB->query($SQL);

    while($aPool = $oPools->fetch_assoc()) {

        $iTimeStamp = strtotime($aPool["MetaTimeStamp"]);
        $dLuck      = floatval($aPool["BlockLuck"]);
        $dDiff      = floatval($aPool["BlockDiff"]);
        $dReward    = floatval($aPool["BlockReward"]);
        $iLastBlock = strtotime($aPool["MetaLastBlock"]);

        if($dDiff > $dLuck) {
            $dLuckP = 100*($dDiff-$dLuck)/$dDiff;
        } else {
            $dLuckP = 100*($dDiff-$dLuck)/$dLuck;
        }
        $dCoinRate = $dLuck/$dReward*intval($aPool["CurrUnit"]);

        echo "<div class='pool-stats'>\n";
        echo "<h2><a href='".$aPool["PoolURL"]."'>".$aPool["PoolName"]."</a></h2>\n";
        echo "<div><b>Last Seen:</b> ".date("Y-m-d H:i:s",$iTimeStamp)."</div>\n";
        echo "<div><b>Activity:</b> ".rdblBigNum($aPool["MetaHashRate"],2,"H/s").", ";
            echo $aPool["MetaMiners"]." miners</div>\n";
        echo "<div><b>Blocks (24h):</b> ".$aPool["BlockCount"]." found, ";
            echo $aPool["BlockOrphaned"]." orphaned</div>\n";
        // echo "<div><b>Last Block:</b> ".rdblSeconds($iTimeStamp-$iLastBlock).", ";
        echo "<div><b>Last Block:</b> ".date("D H:i",$iLastBlock).", ";
            echo $aPool["MetaPending"]." pending</div>\n";
        echo "<div><b>Luck (24h):</b> ".rdblNum($dLuckP,1,"%");
            echo " (".rdblBigNum($dCoinRate,2,"H")."/".$aPool["CurrISO"].")</div>";
        echo "<div><b>Difficulty:</b> ".rdblBigNum($dDiff,2);
            echo " (".rdblBigNum($dDiff/120,2,"H/s").")</div>";

        $SQL  = "SELECT ";
        $SQL .= "pw.PoolID AS PoolID, ";
        $SQL .= "w.Name AS WalletName, ";
        $SQL .= "m.Hashes AS Hashes, ";
        $SQL .= "m.LastShare AS LastShare, ";
        $SQL .= "m.Balance AS Balance, ";
        $SQL .= "m.HashRate AS HashRate ";
        $SQL .= "FROM cryptomonitor.pool_wallet AS pw ";
        $SQL .= "JOIN cryptomonitor.wallets AS w ON w.ID = pw.WalletID ";
        $SQL .= "JOIN (";
        $SQL .=     "SELECT WalletID, PoolID,";
        $SQL .=     "MAX(TimeStamp) AS LatestMining ";
        $SQL .=     "FROM cryptomonitor.mining ";
        $SQL .=     "GROUP BY WalletID, PoolID ";
        $SQL .= ") AS t1 ON t1.WalletID = pw.WalletID AND t1.PoolID = pw.PoolID ";
        $SQL .= "JOIN cryptomonitor.mining AS m ";
        $SQL .=     "ON m.TimeStamp = t1.LatestMining ";
        $SQL .=     "AND m.PoolID = pw.PoolID ";
        $SQL .=     "AND m.WalletID = pw.WalletID ";
        $SQL .= "WHERE pw.PoolID = '".$aPool["PoolID"]."'";
        $oWallets = $oDB->query($SQL);

        while($aWallet = $oWallets->fetch_assoc()) {
            $iHashes   = intval($aWallet["Hashes"]);
            $dHashRate = floatval($aWallet["HashRate"]);
            $iBalance  = intval($aWallet["Balance"])/intval($aPool["CurrDispUnit"]);
            $dCoinRate = $iHashes/intval($aWallet["Balance"])*intval($aPool["CurrUnit"]);

            echo "<h3>".$aWallet["WalletName"]." wallet</h3>\n";
            echo "<div><b>Hashes:</b> ".rdblBigNum($iHashes,2,"H");
                echo " (".rdblBigNum($dCoinRate,2,"H")."/".$aPool["CurrISO"].")</div>\n";
            echo "<div><b>HashRate:</b> ".rdblBigNum($dHashRate,2,"H/s")."</div>\n";
            echo "<div><b>Balance:</b> ".rdblNum($iBalance,2,$aPool["CurrDispName"])."</div>";
        }
        echo "</div>\n";
    }

    echo "<br>\n";

    require_once("includes/footer.php");
?>
