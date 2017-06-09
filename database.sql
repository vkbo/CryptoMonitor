# Currency Table

CREATE TABLE `currency` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(45) DEFAULT NULL,
  `ISO` varchar(45) DEFAULT NULL,
  `ValueUnit` bigint(20) DEFAULT NULL,
  `DisplayName` varchar(45) DEFAULT NULL,
  `DisplayUnit` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Mining Table

CREATE TABLE `mining` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TimeStamp` datetime DEFAULT NULL,
  `WalletID` int(11) DEFAULT NULL,
  `PoolID` int(11) DEFAULT NULL,
  `Hashes` bigint(20) DEFAULT NULL,
  `LastShare` datetime DEFAULT NULL,
  `Balance` bigint(20) DEFAULT NULL,
  `HashRate` double DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MainIDX` (`TimeStamp`,`WalletID`,`PoolID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Mining Hourly Stats Table

CREATE TABLE `mining_hourly` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Hour` datetime DEFAULT NULL,
  `WalletID` int(11) DEFAULT NULL,
  `PoolID` int(11) DEFAULT NULL,
  `Hashes` bigint(20) DEFAULT NULL,
  `Balance` bigint(20) DEFAULT NULL,
  `HashRate` double DEFAULT NULL,
  `Entries` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MainIDX` (`Hour`,`WalletID`,`PoolID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Mining Daily Stats Table

CREATE TABLE `mining_daily` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Day` datetime DEFAULT NULL,
  `WalletID` int(11) DEFAULT NULL,
  `PoolID` int(11) DEFAULT NULL,
  `Hashes` bigint(20) DEFAULT NULL,
  `Balance` bigint(20) DEFAULT NULL,
  `HashRate` double DEFAULT NULL,
  `Entries` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MainIDX` (`Day`,`WalletID`,`PoolID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Pools Table

CREATE TABLE `pools` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `API` varchar(255) DEFAULT NULL,
  `APIType` varchar(45) DEFAULT NULL,
  `APIOptions` varchar(255) DEFAULT NULL,
  `CurrencyID` int(11) DEFAULT NULL,
  `Active` tinyint(1) DEFAULT NULL,
  `Display` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Pool Blocks Table

CREATE TABLE `pool_blocks` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TimeStamp` datetime DEFAULT NULL,
  `PoolID` int(11) DEFAULT NULL,
  `Height` int(11) DEFAULT NULL,
  `Hash` varchar(64) DEFAULT NULL,
  `Difficulty` bigint(20) DEFAULT NULL,
  `FoundTime` datetime DEFAULT NULL,
  `Luck` bigint(20) DEFAULT NULL,
  `Reward` bigint(20) DEFAULT NULL,
  `Orphaned` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LookupIDX` (`Height`,`PoolID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

¤ Pool Payments Table

CREATE TABLE `pool_payments` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TimeStamp` datetime DEFAULT NULL,
  `PoolID` int(11) DEFAULT NULL,
  `WalletID` int(11) DEFAULT NULL,
  `TransactionTime` datetime DEFAULT NULL,
  `TransactionHash` varchar(64) DEFAULT NULL,
  `Amount` bigint(20) DEFAULT NULL,
  `Mixin` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Hash` (`TransactionHash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Pool Meta Data Table

CREATE TABLE `pool_meta` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TimeStamp` datetime DEFAULT NULL,
  `PoolID` int(11) DEFAULT NULL,
  `HashRate` double DEFAULT NULL,
  `Miners` int(11) DEFAULT NULL,
  `NewBlocks` int(11) DEFAULT NULL,
  `PendingBlocks` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Pool Daily Stats Table

CREATE TABLE `pool_daily` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Day` datetime DEFAULT NULL,
  `PoolID` int(11) DEFAULT NULL,
  `AvgDifficulty` bigint(20) DEFAULT NULL,
  `AvgLuck` bigint(20) DEFAULT NULL,
  `AvgReward` bigint(20) DEFAULT NULL,
  `SumReward` bigint(20) DEFAULT NULL,
  `Blocks` int(11) DEFAULT NULL,
  `Orphaned` int(11) DEFAULT NULL,
  `HashRate` double DEFAULT NULL,
  `Miners` int(11) DEFAULT NULL,
  `MetaEntries` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Wallets Table

CREATE TABLE `wallets` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(45) DEFAULT NULL,
  `Owner` varchar(45) DEFAULT NULL,
  `Address` varchar(100) DEFAULT NULL,
  `CurrencyID` int(11) DEFAULT NULL,
  `Active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Pool to Wallet Links Table

CREATE TABLE `pool_wallet` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `PoolID` int(11) NOT NULL,
  `WalletID` int(11) NOT NULL,
  `Display` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
