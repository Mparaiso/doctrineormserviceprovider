<?php

if (isset($_SERVER['SCRIPT_FILENAME'])) {
    return false;
} else {
    require 'app_dev.php';
}
