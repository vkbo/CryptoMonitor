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
    $SQL .= "FROM pools AS p ";
    $SQL .= "JOIN currency AS c ON c.ID = p.CurrencyID ";
    $SQL .= "JOIN (";
    $SQL .=     "SELECT PoolID, ";
    $SQL .=     "MAX(TimeStamp) AS LatestMeta ";
    $SQL .=     "FROM pool_meta ";
    $SQL .=     "GROUP BY PoolID";
    $SQL .= ") AS t1 ON t1.PoolID = p.ID ";
    $SQL .= "JOIN pool_meta AS pm ON pm.TimeStamp = t1.LatestMeta AND pm.PoolID = p.ID ";
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
    $SQL .= "WHERE p.Active = 1 AND p.Display = 1 ";
    $SQL .= "ORDER BY pm.TimeStamp DESC, pm.ID DESC";
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

        if(time()-$iTimeStamp > 1820) {
            $sTimeCol = "red";
        } elseif(time()-$iTimeStamp > 620) {
            $sTimeCol = "orange";
        } else {
            $sTimeCol = "";
        }

        echo "<div class='pool-stats ".$sTimeCol."'>\n";
        echo "<h2><a href='".$aPool["PoolURL"]."' target='_blank'>".$aPool["PoolName"]."</a></h2>\n";
        echo "<div><b>Last Seen:</b> ".date("Y-m-d H:i:s",$iTimeStamp)."</div>\n";
        echo "<div><b>Activity:</b> ".rdblBigNum($aPool["MetaHashRate"],2,"H/s").", ";
            echo $aPool["MetaMiners"]." miners</div>\n";
        echo "<div><b>Last Block:</b> ".date("D H:i",$iLastBlock).", ";
            echo $aPool["MetaPending"]." pending</div>\n";
        echo "<div><b>Blocks (24h):</b> ".$aPool["BlockCount"]." found, ";
            echo $aPool["BlockOrphaned"]." orphaned</div>\n";
        // echo "<div><b>Last Block:</b> ".rdblSeconds($iTimeStamp-$iLastBlock).", ";
        echo "<div><b>Luck (24h):</b> ".rdblNum($dLuckP,1,"%");
            echo " (".rdblBigNum($dCoinRate,2,"H")."/".$aPool["CurrISO"].")</div>";
        echo "<div><b>Diff (24h):</b> ".rdblBigNum($dDiff,2);
            echo " (".rdblBigNum($dDiff/120,2,"H/s").")</div>";

        $SQL  = "SELECT ";
        $SQL .= "pw.PoolID AS PoolID, ";
        $SQL .= "w.Name AS WalletName, ";
        $SQL .= "m.Hashes AS Hashes, ";
        $SQL .= "m.LastShare AS LastShare, ";
        $SQL .= "m.Balance AS Balance, ";
        $SQL .= "m.HashRate AS HashRate, ";
        $SQL .= "IF(SUM(pp.Amount) IS NULL, 0, SUM(pp.Amount)) AS Payments ";
        $SQL .= "FROM pool_wallet AS pw ";
        $SQL .= "JOIN wallets AS w ON w.ID = pw.WalletID ";
        $SQL .= "LEFT JOIN pool_payments AS pp ON pp.PoolID = pw.PoolID AND pp.WalletID = pw.WalletID ";
        $SQL .= "JOIN (";
        $SQL .=     "SELECT WalletID, PoolID, ";
        $SQL .=     "MAX(TimeStamp) AS LatestMining ";
        $SQL .=     "FROM cryptomonitor.mining ";
        $SQL .=     "GROUP BY WalletID, PoolID ";
        $SQL .= ") AS t1 ON t1.WalletID = pw.WalletID AND t1.PoolID = pw.PoolID ";
        $SQL .= "JOIN cryptomonitor.mining AS m ";
        $SQL .=     "ON m.TimeStamp = t1.LatestMining ";
        $SQL .=     "AND m.PoolID = pw.PoolID ";
        $SQL .=     "AND m.WalletID = pw.WalletID ";
        $SQL .= "WHERE pw.PoolID = '".$aPool["PoolID"]."' ";
        $SQL .= "AND pw.Display = 1 ";
        $SQL .= "GROUP BY pw.WalletID";
        $oWallets = $oDB->query($SQL);

        while($aWallet = $oWallets->fetch_assoc()) {
            $iHashes   = intval($aWallet["Hashes"]);
            $dHashRate = floatval($aWallet["HashRate"]);
            $iBalance  = intval($aWallet["Balance"])/intval($aPool["CurrDispUnit"]);
            $iPayments = intval($aWallet["Payments"])/intval($aPool["CurrDispUnit"]);
            $dCoinRate = $iHashes/($iBalance+$iPayments)*1000;
            $dCoinTime = $dCoinRate/$dHashRate;

            echo "<h3>".$aWallet["WalletName"]." Wallet</h3>\n";
            echo "<div><b>Hashes:</b> ".rdblBigNum($iHashes,2,"H");
                echo " (".rdblBigNum($dCoinRate,2,"H")."/".$aPool["CurrISO"].")</div>\n";
            echo "<div><b>HashRate:</b> ".rdblBigNum($dHashRate,2,"H/s");
            if($dCoinTime > 48*3600) {
                echo " (".rdblNum($dCoinTime/86400,2,"d")."/".$aPool["CurrISO"].")</div>\n";
            } else {
                echo " (".rdblNum($dCoinTime/3600,1,"h")."/".$aPool["CurrISO"].")</div>\n";
            }
            echo "<div><b>Balance:</b> ".rdblNum($iBalance,2,$aPool["CurrDispName"])."</div>";
            echo "<div><b>Payments:</b> ".rdblNum($iPayments,2,$aPool["CurrDispName"])."</div>";
        }
        echo "</div>\n";
    }

    echo "<br>\n";

    $SQL  = "SELECT ";
    $SQL .= "pb.TimeStamp AS TimeStamp, ";
    $SQL .= "pb.Height AS Height, ";
    $SQL .= "pb.Difficulty AS Difficulty, ";
    $SQL .= "pb.FoundTime AS FoundTime, ";
    $SQL .= "pb.Hash AS Hash, ";
    $SQL .= "pb.Luck AS Luck, ";
    $SQL .= "pb.Reward AS Reward, ";
    $SQL .= "pb.Orphaned AS Orphaned, ";
    $SQL .= "p.Name AS PoolName, ";
    $SQL .= "p.ID AS PoolID ";
    $SQL .= "FROM pool_blocks AS pb ";
    $SQL .= "JOIN pools AS p ON pb.PoolID = p.ID ";
    $SQL .= "ORDER BY pb.Height DESC ";
    $SQL .= "LIMIT 0,50";
    $oBlocks = $oDB->query($SQL);

    echo "<h2>Blocks</h2>\n";
    echo "<div class='table-wrap'>\n";
    echo "<table class='blocks-list'>\n";
    echo "<thead>\n";
        echo "<tr>";
        echo "<td>Height</td>";
        echo "<td>Pool</td>";
        // echo "<td class='twide'>Hash</td>";
        echo "<td>Found</td>";
        echo "<td>Diff</td>";
        echo "<td>Luck</td>";
        echo "<td>Coin</td>";
        echo "</tr>\n";
    echo "</thead>\n";

    echo "<tbody>\n";
    while($aBlock = $oBlocks->fetch_assoc()) {

        $iLuck   = intval($aBlock["Luck"]);
        $iDiff   = intval($aBlock["Difficulty"]);
        $dReward = intval($aBlock["Reward"])/1e12;

        if($iDiff > $iLuck) {
            $dLuck = 100*($iDiff-$iLuck)/$iDiff;
            $sLuck = "green";
        } else {
            $dLuck = 100*($iDiff-$iLuck)/$iLuck;
            $sLuck = "red";
        }

        $aLegend[$aBlock["PoolID"]] = $aBlock["PoolName"];

        echo "<tr class='".($aBlock["Orphaned"]==1?"block-orph":"block-ok")."'>\n";
        echo "<td>".$aBlock["Height"]."</td>\n";
        echo "<td>";
            echo "<span class='twide'>".$aBlock["PoolName"]."</span>";
            echo "<span class='tnarr' style='display: table; margin: 0 auto;'><div class='tnarr legend l".($aBlock["PoolID"]%6-1)."'></div></span>";
        echo "</td>\n";
        // echo "<td class='twide'>".substr($aBlock["Hash"],0,8)."</td>\n";
        echo "<td>";
            echo "<span class='twide'>".date("Y-m-d H:i:s",strtotime($aBlock["FoundTime"]))."</span>";
            echo "<span class='tnarr'>".date("D H:i",strtotime($aBlock["FoundTime"]))."</span>";
        echo "</td>\n";
        echo "<td class='right'>".rdblBigNum($aBlock["Difficulty"],2)."</td>\n";
        echo "<td class='right ".$sLuck."'>".rdblNum($dLuck,1,"%")."</td>\n";
        echo "<td class='right'>".rdblNum($dReward,2,"")."<span class='twide'> XMR</span></td>\n";
        echo "</tr>\n";
    }
    echo "</tbody>\n";
    echo "</table>\n";
    echo "<div class='tnarr'>\n";
    foreach($aLegend as $iID=>$sPoolName) {
        echo "<div><div class='legend l".($iID-1)."'></div> ".$sPoolName."</div>\n";
    }
    echo "</div>\n";
    echo "</div>\n";

    require_once("includes/footer.php");
?>
