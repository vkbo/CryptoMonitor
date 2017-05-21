<?php
   /**
    *  Crypto Monitor – Cron Pool Script
    * ===================================
    *  Created 2017-05-20 :: Forked from original cron.php
    */

    if(!isset($bCron)) exit();

    // Retrieve JSON data
    function getJsonData($sAPI) {

        $webOpts    = array("http" => array("header" => "User-Agent: Mozilla/5.0"));
        $webContext = stream_context_create($webOpts);
        $jsonData   = file_get_contents($sAPI,false,$webContext);

        if($jsonData === false) {
            return false;
        }

        if(in_array("Content-Encoding: deflate",$http_response_header)) {
            $jsonData = gzinflate($jsonData);
            echo getTimeStamp()." Unpacking data\n";
        }

        return json_decode($jsonData,true);
    }

    // Polling function for node-cryptonote-pool sites
    function poolNodeCryptoNotePool($oDB, $aPool, $bSave=true) {

        $sPoolName = $aPool["Name"];
        $iPoolID   = $aPool["ID"];
        $sPoolAPI  = $aPool["API"];
        $sPoolOpt  = $aPool["APIOptions"];

        echo getTimeStamp()." Polling ".$sPoolName."\n";

        // Get Main Stats
        $aStats  = getJsonData($sPoolAPI."/stats");
        if($aStats === false) {
            echo getTimeStamp()." Could not connect to API\n";
            return;
        }

        $SQL  = "SELECT Height ";
        $SQL .= "FROM pool_blocks ";
        $SQL .= "WHERE PoolID = '".$iPoolID."' ";
        $SQL .= "ORDER BY Height DESC ";
        $SQL .= "LIMIT 0, 1";
        $oLastBlock = $oDB->query($SQL);

        if($oLastBlock->num_rows > 0) {
            $aLastBlock = $oLastBlock->fetch_assoc();
            $iLastBlock = $aLastBlock["Height"];
        } else {
            $iLastBlock = 0;
        }

        $nNew     = 0;
        $nPending = 0;
        $nBlocks  = floor(count($aStats["pool"]["blocks"])/2);

        for($i=0; $i<$nBlocks; $i++) {
            $sBlocks = $aStats["pool"]["blocks"][$i*2];
            $sHeight = $aStats["pool"]["blocks"][$i*2+1];

            if(intval($sHeight) > $iLastBlock) {

                $aBlocks = explode(":",$sBlocks);
                $nBits   = count($aBlocks);

                // Check that the block is mature
                $bPending = false;
                switch($sPoolOpt) {
                    case "cpfr":
                        // Customised API with longer block strings
                        if($sPoolOpt == "cpfr" && $nBits != 10) {
                            $nPending++;
                            $bPending = true;
                        }
                        break;
                    default:
                        if($nBits != 6) {
                            $nPending++;
                            $bPending = true;
                        }
                        break;
                }
                if($bPending) continue;

                $sHash = $aBlocks[0];
                $sTime = date("Y-m-d-H-i-s",intval($aBlocks[1]));
                $iDiff = intval($aBlocks[2]);
                $iLuck = intval($aBlocks[3]);
                $iOrph = intval($aBlocks[4]);
                $iPaid = intval($aBlocks[5]);

                if($iOrph > 1) {
                    echo getTimeStamp()." Unknown block status for block ".$sHeight."\n";
                    continue;
                }

                $SQL  = "INSERT INTO pool_blocks (";
                $SQL .= "PoolID, ";
                $SQL .= "Height, ";
                $SQL .= "Hash, ";
                $SQL .= "Difficulty, ";
                $SQL .= "FoundTime, ";
                $SQL .= "Luck, ";
                $SQL .= "Reward, ";
                $SQL .= "Orphaned";
                $SQL .= ") VALUES (";
                $SQL .= "'".$iPoolID."',";
                $SQL .= "'".$sHeight."',";
                $SQL .= "'".$sHash."',";
                $SQL .= "'".$iDiff."',";
                $SQL .= "'".$sTime."',";
                $SQL .= "'".$iLuck."',";
                $SQL .= "'".$iPaid."',";
                $SQL .= "'".$iOrph."')";
                if($bSave) $oDB->query($SQL);

                $nNew++;
            }
        }
        echo getTimeStamp()." ".$nNew." new block".($nNew!=1?"s":"").", ".$nPending." pending\n";

        // Pool Meta
        $dRate   = floatval($aStats["pool"]["hashrate"]);
        $iMiners = intval($aStats["pool"]["miners"]);

        $SQL  = "INSERT INTO pool_meta (";
        $SQL .= "PoolID, ";
        $SQL .= "TimeStamp, ";
        $SQL .= "HashRate, ";
        $SQL .= "Miners,";
        $SQL .= "NewBlocks,";
        $SQL .= "PendingBlocks";
        $SQL .= ") VALUES (";
        $SQL .= "'".$iPoolID."', ";
        $SQL .= "'".date("Y-m-d-H-i-s",time())."', ";
        $SQL .= "'".$dRate."', ";
        $SQL .= "'".$iMiners."',";
        $SQL .= "'".$nNew."',";
        $SQL .= "'".$nPending."')";
        if($bSave) $oDB->query($SQL);

        // Wallet Stats
        $SQL  = "SELECT ";
        $SQL .= "w.ID AS ID, ";
        $SQL .= "w.Name AS Name, ";
        $SQL .= "w.Address AS Address ";
        $SQL .= "FROM wallets AS w ";
        $SQL .= "LEFT JOIN pool_wallet AS pw ON w.ID = pw.WalletID ";
        $SQL .= "WHERE pw.PoolID = '".$iPoolID."'";
        $oWallets = $oDB->query($SQL);

        if($oWallets === false) {
            echo getTimeStamp()." Wallets query error. Breaking\n";
            echo getTimeStamp()." SQL: ".preg_replace("!\s+!"," ",$SQL)."\n";
            echo getTimeStamp()." Error: ".$oDB->error."\n";
            return;
        }

        while($aWallet = $oWallets->fetch_assoc()) {

            // Get Stats
            echo getTimeStamp()." Getting stats for wallet ".$aWallet["Name"]." on ".$sPoolName."\n";
            $aMining    = getJsonData($sPoolAPI."/stats_address?longpool=false&address=".$aWallet["Address"]);
            if($aMining === false) {
                echo getTimeStamp()." Could not connect to API\n";
                return;
            }
            $iHashes    = intval(array_key_exists("hashes",$aMining["stats"]) ? $aMining["stats"]["hashes"] : 0);
            $iLastShare = intval(array_key_exists("lastShare",$aMining["stats"]) ? $aMining["stats"]["lastShare"] : 0);
            $sLastShare = date("Y-m-d-H-i-s",$iLastShare);
            $iBalance   = intval(array_key_exists("balance",$aMining["stats"]) ? $aMining["stats"]["balance"] : 0);

            $SQL  = "SELECT TimeStamp, Hashes ";
            $SQL .= "FROM mining ";
            $SQL .= "WHERE TimeStamp >= '".date("Y-m-d-H-i-s",time()-3600)."' ";
            $SQL .= "AND WalletID = '".$aWallet["ID"]."' ";
            $SQL .= "AND PoolID = '".$iPoolID."' ";
            $SQL .= "ORDER BY TimeStamp ";
            $SQL .= "LIMIT 0,1";
            $oPrev = $oDB->query($SQL);

            if($oPrev->num_rows > 0) {
                $aPrev = $oPrev->fetch_assoc();
                $iPrevTime = strtotime($aPrev["TimeStamp"]);
                $iPrevHash = intval($aPrev["Hashes"]);
                if($iPrevHash > 0) {
                    $iTimeDiff = $iLastShare-$iPrevTime;
                    $iHashDiff = $iHashes-$iPrevHash;
                    $dHashRate = $iHashDiff/$iTimeDiff;
                } else {
                    $dHashRate = 0.0;
                }
            } else {
                $dHashRate = 0.0;
            }

            $SQL  = "INSERT INTO mining (TimeStamp,WalletID,PoolID,Hashes,LastShare,Balance,HashRate) VALUES (";
            $SQL .= "'".date("Y-m-d-H-i-s",time())."',";
            $SQL .= "'".$aWallet["ID"]."',";
            $SQL .= "'".$iPoolID."',";
            $SQL .= "'".$iHashes."',";
            $SQL .= "'".$sLastShare."',";
            $SQL .= "'".$iBalance."',";
            $SQL .= "'".$dHashRate."')";
            if($bSave) $oDB->query($SQL);
        }
        $oWallets->close();
    }
?>
