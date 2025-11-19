<?php
require "db_master.php";
require "db_read.php";

// Добавление блюда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['price'], $_POST['category_id'])) {
    $name = $master_conn->real_escape_string($_POST['name']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];

    $master_conn->query("INSERT INTO dishes (name, price, category_id) 
                         VALUES ('$name', $price, $category_id)");
    header("Location: index.php");
    exit;
}

// Удаление блюда
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $master_conn->query("DELETE FROM dishes WHERE id=$id");
    header("Location: index.php");
    exit;
}

// Читаем категории и блюда с REPLICA
$categories = $read_conn->query("SELECT * FROM categories");
$dishes = $read_conn->query("
    SELECT dishes.id, dishes.name, dishes.price, categories.name AS category 
    FROM dishes 
    LEFT JOIN categories ON dishes.category_id = categories.id
");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Меню ресторана</title>

    <style>
        body {
            font-family: Arial;
            margin: 40px;
            background: #fafafa;
        }
        h2 {
            color: #333;
        }
        table {
            border-collapse: collapse;
            width: 500px;
            background: white;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        td {
            background: #fff;
        }
        th {
            background: #f7f7f7;
        }
        form {
            width: 400px;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-top: 8px;
            margin-bottom: 12px;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .delete-btn {
            color: red;
            text-decoration: none;
        }
    </style>

</head>
<body>

<h2>Меню ресторана</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Блюдо</th>
        <th>Цена</th>
        <th>Категория</th>
        <th>Удалить</th>
    </tr>

    <?php while($row = $dishes->fetch_assoc()) : ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= $row['price'] ?> $</td>
            <td><?= $row['category'] ?></td>
            <td>
                <a class="delete-btn" href="?delete=<?= $row['id'] ?>">X</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<h2>Добавить блюдо</h2>

<form method="POST">
    <label>Название блюда:</label>
    <input type="text" name="name" required>

    <label>Цена:</label>
    <input type="number" step="0.01" name="price" required>

    <label>Категория:</label>
    <select name="category_id" required>
        <?php while($cat = $categories->fetch_assoc()): ?>
            <option value="<?= $cat['id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Добавить</button>
</form>

</body>
</html>