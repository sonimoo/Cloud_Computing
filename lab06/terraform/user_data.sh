#!/bin/bash
set -e

# Обновление системы
dnf update -y

# Установка Nginx + PHP-FPM
dnf install -y nginx php php-fpm php-cli

# Включаем автозапуск сервисов
systemctl enable nginx
systemctl enable php-fpm

# Создаём директорию под сайт
mkdir -p /var/www/html

cat > /var/www/html/index.php << 'EOF'
<?php
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($requestUri === '/load') {
    runCpuLoad();
} else {
    showHome();
}

function runCpuLoad(): void
{
    ini_set('max_execution_time', '600');

    $seconds = isset($_GET['seconds']) ? (int)$_GET['seconds'] : 180;
    $seconds = max(30, min($seconds, 600)); // от 30 до 600 сек

    $endTime = microtime(true) + $seconds;

    $seed = random_int(1, 1_000_000);
    $dummy = 0.0;

    while (microtime(true) < $endTime) {
        for ($i = 0; $i < 2_000_000; $i++) {
            $x = $seed + $i;
            $dummy += sqrt($x) * sin($x) * cos($x) + log($x + 1);
        }
        $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo "Heavy CPU load finished\n";
    echo "Duration: {$seconds} seconds\n";
    echo "Dummy value: " . sprintf('%.5f', fmod($dummy, 1000.0)) . "\n";
}

function showHome(): void
{
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Hello from " . htmlspecialchars(gethostname()) . "</h1>";
    echo "<p><a href=\"/load\">Load system (/load)</a></p>";
    echo "<p>e.g.: <code>/load?seconds=60</code></p>";
}
EOF

# Настройка nginx
cat > /etc/nginx/conf.d/default.conf << 'EOF'
server {
    listen 80 default_server;
    server_name _;

    root /var/www/html;
    index index.php index.html;

    location / {
        try_files $uri /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php-fpm/www.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
EOF

# Удаляем дефолтный конфиг (если есть)
rm -f /etc/nginx/conf.d/*.default

# Меняем права
chown -R nginx:nginx /var/www/html

# Проверяем и стартуем всё
nginx -t
systemctl restart php-fpm
systemctl restart nginx