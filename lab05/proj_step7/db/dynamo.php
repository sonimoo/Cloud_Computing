<?php
require __DIR__ . '/../vendor/autoload.php';  // composer автоматически создаст autoload
use Aws\DynamoDb\DynamoDbClient;

$dynamodb = new DynamoDbClient([
    'region' => 'eu-central-1',
    'version' => 'latest',
]);

$tableName = 'Dishes';
