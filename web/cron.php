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

    while($aPool = $oPools->fetch_assoc()) {

        $sPoolType = $aPool["APIType"];
        $sPoolName = $aPool["Name"];
        $iPoolID   = $aPool["ID"];
        $sPoolAPI  = $aPool["API"];

        switch($sPoolType) {
            case "node-cryptonote-pool":

                // Get Main Stats
                echo getTimeStamp()." Polling ".$sPoolName."\n";
                $jsonData = file_get_contents($sPoolAPI."/stats",false,$webContext);
                if($jsonData === false) {
                    echo getTimeStamp()." Could not connect to API\n";
                    continue;
                }
                if(in_array("Content-Encoding: deflate",$http_response_header)) {
                    $jsonData = gzinflate($jsonData);
                    echo getTimeStamp()." Unpacking data\n";
                }
                $aStats  = json_decode($jsonData,true);

                $dRate   = floatval($aStats["pool"]["hashrate"]);
                $iMiners = intval($aStats["pool"]["miners"]);

                $SQL  = "INSERT INTO pool_meta (PoolID,TimeStamp,HashRate,Miners) VALUES (";
                $SQL .= "'".$iPoolID."',";
                $SQL .= "'".date("Y-m-d-H-i-s",time())."',";
                $SQL .= "'".$dRate."',";
                $SQL .= "'".$iMiners."')";
                $oDB->query($SQL);

                $nBlocks = floor(count($aStats["pool"]["blocks"])/2);

                $SQL = sprintf(
                   "SELECT Height
                    FROM pool_blocks
                    WHERE PoolID = '%s'
                    ORDER BY Height DESC
                    LIMIT 0, 1",
                    $iPoolID
                );
                $oLastBlock = $oDB->query($SQL);
                if($oLastBlock->num_rows > 0) {
                    $aLastBlock = $oLastBlock->fetch_assoc();
                    $iLastBlock = $aLastBlock["Height"];
                } else {
                    $iLastBlock = 0;
                }
                $nNew = 0;

                for($i=0; $i<$nBlocks; $i++) {
                    $sBlocks = $aStats["pool"]["blocks"][$i*2];
                    $sHeight = $aStats["pool"]["blocks"][$i*2+1];

                    if(intval($sHeight) > $iLastBlock) {

                        $aBlocks = explode(":",$sBlocks);
                        if(count($aBlocks) < 6) continue;
                        if(intval($aBlocks[4]) != 0) continue;

                        $sHash = $aBlocks[0];
                        $sTime = date("Y-m-d-H-i-s",intval($aBlocks[1]));
                        $iDiff = intval($aBlocks[2]);
                        $iLuck = intval($aBlocks[3]);
                        $iPaid = intval($aBlocks[5]);

                        $SQL  = "INSERT INTO pool_blocks (";
                        $SQL .= "PoolID, Height, Hash, Difficulty, FoundTime, Luck, Reward, Share";
                        $SQL .= ") VALUES (";
                        $SQL .= "'".$iPoolID."',";
                        $SQL .= "'".$sHeight."',";
                        $SQL .= "'".$sHash."',";
                        $SQL .= "'".$iDiff."',";
                        $SQL .= "'".$sTime."',";
                        $SQL .= "'".$iLuck."',";
                        $SQL .= "'".$iPaid."',";
                        $SQL .= "'0')";
                        $oDB->query($SQL);

                        $nNew++;
                    }
                }
                if($nNew > 0) {
                    echo getTimeStamp()." Added ".$nNew." new block".($nNew>1?"s":"")."\n";
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
                    $iPoolID
                );
                $oWallets = $oDB->query($SQL);
                if($oWallets === false) {
                    echo getTimeStamp()." Wallets query error. Breaking\n";
                    echo getTimeStamp()." SQL: ".preg_replace("!\s+!"," ",$SQL)."\n";
                    echo getTimeStamp()." Error: ".$oDB->error."\n";
                    break;
                }
                while($aWallet = $oWallets->fetch_assoc()) {

                    // Get Stats
                    echo getTimeStamp()." Getting stats for wallet ".$aWallet["Name"]."@".$sPoolName."\n";
                    $sAPICall = $sPoolAPI."/stats_address?longpool=false&address=".$aWallet["Address"];
                    $jsonData = file_get_contents($sAPICall,false,$webContext);
                    if($jsonData === false) {
                        echo getTimeStamp()." Could not connect to API\n";
                        continue;
                    }
                    if(in_array("Content-Encoding: deflate",$http_response_header)) {
                        $jsonData = gzinflate($jsonData);
                        echo getTimeStamp()." Unpacking data\n";
                    }
                    $aMining = json_decode($jsonData,true);

                    $iHashes    = intval(array_key_exists("hashes",$aMining["stats"]) ? $aMining["stats"]["hashes"] : 0);
                    $iLastShare = intval(array_key_exists("lastShare",$aMining["stats"]) ? $aMining["stats"]["lastShare"] : 0);
                    $sLastShare = date("Y-m-d-H-i-s",$iLastShare);
                    $iBalance   = intval(array_key_exists("balance",$aMining["stats"]) ? $aMining["stats"]["balance"] : 0);

                    $SQL = sprintf(
                       "SELECT TimeStamp, Hashes
                        FROM mining
                        WHERE TimeStamp >= '%s' AND WalletID = '%s' AND PoolID = '%s'
                        ORDER BY TimeStamp
                        LIMIT 0,1",
                        date("Y-m-d-H-i-s",time()-3600),
                        $aWallet["ID"],
                        $iPoolID
                    );
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

                    $oDB->query($SQL);

                }
                $oWallets->close();

                break;

            default:
                echo getTimeStamp()." Unknown API Type\n";
                break;
        }
    }

    $oPools->close();
    $oDB->close();
?>
