<?php

if (!file_exists(__DIR__ . "/mycro.config")){
    header("Location: __mycro-setup.php");
    die();
}