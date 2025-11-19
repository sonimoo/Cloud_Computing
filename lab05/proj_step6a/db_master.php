<?php
$host = "project-rds-mysql-prod.xxxxxxxx.region.rds.amazonaws.com";
$user = "admin";
$pass = "************";
$db   = "project_db";

$master_conn = new mysqli($host, $user, $pass, $db);

if ($master_conn->connect_error) {
    die("Ошибка подключения к MASTER: " . $master_conn->connect_error);
}