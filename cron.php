<?php
   /**
    *  Crypto Monitor – Cron Script
    * ==============================
    *  Created 2017-05-17
    */

    require_once("config.php");

    $oDB = new mysqli($cDBHost,$cDBUser,$cDBPass,$cDBMain);
    $webOpts = array("http" => array("header" => "User-Agent: Mozilla/5.0"));
    $webContext = stream_context_create($webOpts);

    $oPools = $oDB->query("SELECT * FROM pools WHERE Active = 1");

    function getTimeStamp() {return date("[Y-m-d H:i:s]",time());};

    while($aRow = $oPools->fetch_assoc()) {
        switch($aRow["APIType"]) {
            case "node-cryptonote-pool":

                // Get Main Stats
                echo getTimeStamp()." Polling ".$aRow["Name"]." ... ";
                $jsonData = file_get_contents($aRow["API"]."/stats",false,$webContext);
                $aStats   = json_decode($jsonData,true);

                $dRate   = floatval($aStats["pool"]["hashrate"]);
                $iMiners = intval($aStats["pool"]["miners"]);

                $SQL  = "INSERT INTO pool_meta (PoolID,TimeStamp,HashRate,Miners) VALUES (";
                $SQL .= "'".$aRow["ID"]."',";
                $SQL .= "'".date("Y-m-d-H-i-s",time())."',";
                $SQL .= "'".$dRate."',";
                $SQL .= "'".$iMiners."')";
                $oDB->query($SQL);
                echo "Success\n";

                $nBlocks = floor(count($aStats["pool"]["blocks"])/2);

                $SQL = sprintf(
                   "SELECT Height
                    FROM pool_blocks
                    WHERE PoolID = '%s'
                    ORDER BY Height DESC
                    LIMIT 0, 1",
                    $aRow["ID"]
                );
                $oLastBlock = $oDB->query($SQL);
                if($oLastBlock->num_rows > 0) {
                    $aLastBlock = $oLastBlock->fetch_assoc();
                    $iLastBlock = $aLastBlock["Height"];
                } else {
                    $iLastBlock = 0;
                }
                $nNew = 0;
                $SQL  = "";

                for($i=0; $i<$nBlocks; $i++) {
                    $sBlocks = $aStats["pool"]["blocks"][$i*2];
                    $sHeight = $aStats["pool"]["blocks"][$i*2+1];

                    if(intval($sHeight) > $iLastBlock) {

                        $aBlocks = explode(":",$sBlocks);
                        if(count($aBlocks) < 6) continue;

                        $sHash = $aBlocks[0];
                        $sTime = date("Y-m-d-H-i-s",intval($aBlocks[1]));
                        $iDiff = intval($aBlocks[2]);
                        $iLuck = intval($aBlocks[3]);
                        $iPaid = intval($aBlocks[5]);

                        $SQL .= "INSERT INTO pool_blocks (";
                        $SQL .= "PoolID, Height, Hash, Difficulty, FoundTime, Luck, Reward, Share";
                        $SQL .= ") VALUES (";
                        $SQL .= "'".$aRow["ID"]."',";
                        $SQL .= "'".$sHeight."',";
                        $SQL .= "'".$sHash."',";
                        $SQL .= "'".$iDiff."',";
                        $SQL .= "'".$sTime."',";
                        $SQL .= "'".$iLuck."',";
                        $SQL .= "'".$iPaid."',";
                        $SQL .= "'0');\n";

                        $nNew++;
                    }
                }
                $oDB->multi_query($SQL);
                if($nNew > 0) {
                    echo getTimeStamp()." Added ".$nNew." new blocks\n";
                } else {
                    echo getTimeStamp()." No new blocks\n";
                }

                // Wallet Stats
                $SQL = sprintf(
                   "SELECT
                    w.ID AS ID,
                    w.Name AS Name,
                    w.Address AS Address
                    FROM wallets AS w
                    LEFT JOIN pool_wallet AS pw ON w.ID = pw.WalletID
                    WHERE pw.PoolID = '%s'",
                    $aRow["ID"]
                );
                $oWallets = $oDB->query($SQL);
                while($aWallets = $oWallets->fetch_assoc()) {
                    echo getTimeStamp()." Getting stats for wallet ".$aWallets["Name"]." ... ";
                    $jsonData = file_get_contents($aRow["API"]."/stats_address?longpool=false&address=".$aWallets["Address"],false,$webContext);
                    $aMining  = json_decode($jsonData,true);
                    echo "Success\n";

                    $iHashes    = intval(array_key_exists("hashes",$aMining["stats"]) ? $aMining["stats"]["hashes"] : 0);
                    $iLastShare = intval(array_key_exists("lastShare",$aMining["stats"]) ? $aMining["stats"]["lastShare"] : 0);
                    $sLastShare = date("Y-m-d-H-i-s",$iLastShare);
                    $iBalance   = intval(array_key_exists("balance",$aMining["stats"]) ? $aMining["stats"]["balance"] : 0);

                    $SQL = "SELECT TimeStamp, Hashes FROM mining WHERE TimeStamp >= '".date("Y-m-d-H-i-s",time()-3600)."' ORDER BY TimeStamp LIMIT 0,1";
                    $oPrev = $oDB->query($SQL);
                    if($oPrev->num_rows > 0) {
                        $aPrev = $oPrev->fetch_assoc();
                        $iTimeDiff = $iLastShare-strtotime($aPrev["TimeStamp"]);
                        $iHashDiff = $iHashes-intval($aPrev["Hashes"]);
                        $dHashRate = $iHashDiff/$iTimeDiff;
                    } else {
                        $dHashRate  = 0.0;
                    }

                    $SQL  = "INSERT INTO mining (TimeStamp,WalletID,PoolID,Hashes,LastShare,Balance,HashRate) VALUES (";
                    $SQL .= "'".date("Y-m-d-H-i-s",time())."',";
                    $SQL .= "'".$aWallets["ID"]."',";
                    $SQL .= "'".$aRow["ID"]."',";
                    $SQL .= "'".$iHashes."',";
                    $SQL .= "'".$sLastShare."',";
                    $SQL .= "'".$iBalance."',";
                    $SQL .= "'".$dHashRate."')";

                    $oDB->query($SQL);
                }

                break;

            default:
                echo getTimeStamp()." Unknown API Type\n";
                break;
        }
    }
?>
