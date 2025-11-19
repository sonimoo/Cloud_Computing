<?php
// Показываем ошибки для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "db/dynamo.php";

// Добавление блюда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['price'], $_POST['category'])) {
    $name = $_POST['name'];
    $price = (float)$_POST['price'];
    $category = $_POST['category'];

    try {
        $dynamodb->putItem([
            'TableName' => $tableName,
            'Item' => [
                'DishID'   => ['S' => uniqid()],
                'Name'     => ['S' => $name],
                'Price'    => ['N' => strval($price)],
                'Category' => ['S' => $category]
            ]
        ]);
    } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
        echo "Ошибка при добавлении блюда: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
}

// Удаление блюда
if (isset($_GET['delete'])) {
    $dishID = $_GET['delete'];

    try {
        $dynamodb->deleteItem([
            'TableName' => $tableName,
            'Key' => [
                'Category' => ['S' => $_GET['category']], // Нужно передать категорию для ключа
                'DishID'   => ['S' => $dishID]
            ]
        ]);
    } catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
        echo "Ошибка при удалении блюда: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
}

// Получаем список всех блюд
try {
    $result = $dynamodb->scan([
        'TableName' => $tableName
    ]);
    $dishes = $result['Items'];
} catch (Aws\DynamoDb\Exception\DynamoDbException $e) {
    echo "Ошибка при чтении таблицы: " . $e->getMessage();
    $dishes = [];
}

// Получаем уникальные категории для select
$categories = [];
foreach ($dishes as $item) {
    $cat = $item['Category']['S'];
    if (!in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Меню ресторана</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<h2>Меню ресторана</h2>

<table>
    <tr>
        <th>Блюдо</th>
        <th>Цена</th>
        <th>Категория</th>
        <th>Удалить</th>
    </tr>

    <?php foreach($dishes as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['Name']['S']) ?></td>
            <td><?= $row['Price']['N'] ?> $</td>
            <td><?= $row['Category']['S'] ?></td>
            <td>
                <a class="delete-btn" 
                   href="?delete=<?= $row['DishID']['S'] ?>&category=<?= urlencode($row['Category']['S']) ?>">X</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<h2>Добавить блюдо</h2>

<form method="POST">
    <label>Название блюда:</label>
    <input type="text" name="name" required>

    <label>Цена:</label>
    <input type="number" step="0.01" name="price" required>

    <label>Категория:</label>
    <select name="category" required>
        <?php foreach($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Добавить</button>
</form>

</body>
</html>
