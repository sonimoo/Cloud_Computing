<?php
include "db_master.php";

$id = $_GET["id"];

$stmt = $master_conn->prepare("DELETE FROM todos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: index.php");
exit;
