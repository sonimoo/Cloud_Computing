<?php
include "db_master.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST["title"];
    $status = $_POST["status"];

    $stmt = $master_conn->prepare("INSERT INTO todos (title, status) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $status);
    $stmt->execute();

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Создание заказа</title>
</head>
<body>
<h1>Создать заказ (MASTER)</h1>

<form method="POST">
    Название заказа:<br>
    <input type="text" name="title" required><br><br>

    Статус:<br>
    <select name="status">
        <option value="new">Новый</option>
        <option value="cooking">Готовится</option>
        <option value="done">Готов</option>
    </select><br><br>

    <button type="submit">Создать</button>
</form>

</body>
</html>