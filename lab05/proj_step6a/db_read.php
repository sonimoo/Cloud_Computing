<?php
$host = "project-rds-mysql-read-replica.xxxxxxx.region.rds.amazonaws.com"; // endpoint реплики
$user = "admin";
$pass = "ВАШ_ПАРОЛЬ";
$db   = "project_db";

$read_conn = new mysqli($host, $user, $pass, $db);

if ($read_conn->connect_error) {
    die("Ошибка подключения к READ REPLICA: " . $read_conn->connect_error);
}
