<?php
require_once 'github.php';
$github = new Github();
$merged_at = '2023-12-01..2023-12-25';
$github->exec($merged_at);