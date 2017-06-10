<?php
   /**
    *  Crypto Monitor – Header File
    * ==============================
    *  Created 2017-05-25
    */

    if(!isset($bMain)) exit();
?><!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="css/normalize.css" type="text/css" media="all">
    <link rel="stylesheet" href="css/styles.css" type="text/css" media="all">
    <link href="https://fonts.googleapis.com/css?family=Source+Code+Pro" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <link rel="icon" href="/icon.png">
    <title>CryptoMonitor</title>
</head>

<body>
    <!-- Begin Header -->
    <header id="header"><h1>Crypto Monitor</h1></header>
    <nav id="main-menu">
        <b>Menu:</b>
        <ul>
            <?php
                echo "<li><a href='index.php'>Main</a></li>";
                echo "<li><a href='miners.php'>Miners</a></li>";
                echo "<li><a href='node.php'>Node</a></li>";
                echo "<li><a href='settings.php'>Settings</a></li>";
            ?>
        </ul>
    </nav>
    <!-- End Header -->

    <!-- Begin Content -->
    <div id="content">
