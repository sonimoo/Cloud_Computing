# Лабораторная работа №6. Балансирование нагрузки в облаке и авто-масштабирование

 - **Калинкова София, I2302** 
 - **30.11.2025** 

## Цель работы

Закрепить навыки работы с AWS EC2, Elastic Load Balancer, Auto Scaling и CloudWatch, создав отказоустойчивую и автоматически масштабируемую архитектуру.

Развернуты:

- VPC с публичными и приватными подсетями;
- Виртуальная машина с веб-сервером (nginx);
- Application Load Balancer;
- Auto Scaling Group (на основе AMI);
- нагрузочный тест с использованием CloudWatch.

## Ход работы

### Шаг 1. Подготовка инфраструктуры с помощью Terraform

В рамках лабораторной работы вместо ручного создания ресурсов в AWS была выполнена автоматизация инфраструктуры с использованием Terraform.

**1. В рабочей директории был создан Terraform-проект.** 

После этого выполнена команда: terraform init. Она загрузила необходимые провайдеры (AWS).

**2. Создание VPC**

С помощью Terraform был описан ресурс aws_vpc, обеспечивающий:

- CIDR-блок: 10.0.0.0/16
- Включён DNS hostnames и DNS support (требуется для корректной работы подсетей и EC2)

**3. Создание двух публичных и двух приватных подсетей**

Для размещения EC2 и будущих Auto Scaling ресурсов были созданы подсети в двух зонах доступности (`us-east-1a` и u`s-east-1b`):

- Публичные подсети: 10.0.1.0/24, 10.0.2.0/24
- Приватные подсети: 10.0.11.0/24, 10.0.12.0/24

Каждая подсеть была описана отдельным Terraform-ресурсом aws_subnet

**4. Создание и прикрепление Internet Gateway**

Для выхода публичных подсетей в интернет создан ресурс:

```
resource "aws_internet_gateway" "igw" {
  vpc_id = aws_vpc.main.id
}
```

**5. Настройка маршрутов**

Создана Route Table для публичных подсетей:

- маршрут `0.0.0.0/0` направлен на `Internet Gateway`.
- Публичные подсети ассоциированы с этой RT (ресурс aws_route_table_association).

### Шаг 2. Создание и настройка виртуальной машины

**1. Описание параметров EC2 в Terraform**

В Terraform был создан ресурс EC2-инстанса:

- AMI: Amazon Linux 2
- Тип: t3.micro (подходит под Free Tier)
- Размещение в публичной подсети 10.0.1.0/24
- Включена выдача публичного IP: associate_public_ip_address = true
- Включён расширенный мониторинг: monitoring = true
- Указан раздел user_data для автоматической установки nginx

**2. Создание Security Group**

Terraform создал SG со следующими правилами:

Входящие:

- SSH (22) — доступ только с моего IP
- HTTP (80) — доступ с любого IP (0.0.0.0/0)

Исходящие:

Полный доступ в интернет (0.0.0.0/0)

 #### Применение инфраструктуры и проверка работы

![alt text](img/image.png)

![alt text](img/image-1.png)
![alt text](img/image-2.png)

После `terraform apply` Terraform создал:

- VPC
- 4 подсети
- Internet Gateway
- Route Table
- Security Group
- EC2 instance

#### Проверка состояния EC2

После развёртывания проверено в AWS Console:

- Статус → 3/3 checks passed
- Public IPv4 был назначен корректно

![alt text](img/image-3.png)
![alt text](img/image-4.png)

В целом все создалось, наглядно:

![alt text](img/image-6.png)
![alt text](img/image-7.png)
![alt text](img/image-8.png)

![alt text](img/image-9.png)

#### Проверка работы веб-сервера
![alt text](img/image-10.png)

Видим, что nginx не найден

![alt text](img/image-11.png)

поменяла ами на 2023

![alt text](img/image-12.png)
![alt text](img/image-13.png)

Теперь когда в браузере открыт публичный IP EC2.
Отобразилась стандартная стартовая страница nginx, что подтверждает:

- успешную установку nginx через UserData
- правильную работу маршрутизации
- доступность веб-сервера

### Шаг 3. Создание AMI

1. В EC2 выбираем `Instance` → `Actions` → `Image and templates` → `Create image`.
![alt text](img/image-14.png)

2. Назовем AMI: `project-web-server-ami`.
![alt text](img/image-15.png)

3. Дождидаемся появления AMI в разделе AMIs.

> Что такое image и чем он отличается от snapshot? Какие есть варианты использования AMI?
> `Image (AMI)` — это готовый шаблон для запуска EC2-инстансов.
`Snapshot` — это резервная копия EBS-диска.
> Разница: AMI включает настройки для загрузки системы (плюс snapshot root-диска), а snapshot — только данные диска.
> Варианты использования AMI
> - Запуск новых EC2-инстансов.
> - Создание собственных кастомных образов.
> - Использование в Auto Scaling.


### Шаг 4. Создание Launch Template

На основе Launch Template в дальнейшем будет создаваться Auto Scaling Group, то есть подниматься новые инстансы по шаблону.

1. В разделе EC2 выбираем `Launch Templates` → `Create launch template`.
2. Указываем следующие параметры:
   1. Название: `project-launch-template`
   ![alt text](img/image-16.png)
   2. AMI: выбираем созданную ранее AMI (`My AMIs` -> `project-web-server-ami`).
   ![alt text](img/image-17.png)
   3. Тип инстанса: `t3.micro`.
   ![alt text](img/image-18.png)
   4. Security groups: выбираем ту же группу безопасности, что и для виртуальной машины.
   ![alt text](img/image-19.png)
   5. Нажимаем `Create launch template`.
   6. В разделе `Advanced details` -> `Detailed CloudWatch monitoring` выбираем `Enable`. Это позволит собирать дополнительные метрики для Auto Scaling.
   ![alt text](img/image-20.png)
   ![alt text](img/image-21.png)

> Что такое Launch Template и зачем он нужен? Чем он отличается от Launch Configuration?
> `Launch Template` — это шаблон с настройками для запуска EC2-инстансов. Он включает параметры: AMI, тип инстанса, диски, сети, SG, UserData и т.д. Нужен для автоматизации создания EC2 и для работы Auto Scaling.
> `Launch Template` можно редактировать и версионировать, а `Launch Configuration` — нет, его каждый раз нужно создавать заново.

### Шаг 5. Создание Target Group

1. В разделе EC2 выбираем `Target Groups` → `Create target group`.
2. Указываем следующие параметры:

   1. Название: `project-target-group`
   2. Тип: `Instances`
   3. Протокол: `HTTP`
   4. Порт: `80`
   ![alt text](img/image-22.png)
   5. VPC: выберите созданную VPC
   ![alt text](img/image-23.png)

3. Нажимаем `Next` -> `Next`, затем `Create target group`.
![alt text](img/image-24.png)

> Зачем необходим и какую роль выполняет Target Group?
> Target Group направляет трафик от балансировщика на конкретные серверы и следит за их здоровьем, выбирая только доступные инстансы.

### Шаг 6. Создание Application Load Balancer

1. В разделе EC2 выбираем `Load Balancers` → `Create Load Balancer` → `Application Load Balancer`.
2. Указываем следующие параметры:
   1. Название: `project-alb`
   2. Scheme: `Internet-facing`.
      > В чем разница между Internet-facing и Internal?
      >Internet-facing — балансировщик виден из интернета и принимает трафик от внешних пользователей.
      > Internal — балансировщик доступен только внутри VPC и обслуживает внутренние ресурсы.
      
      ![alt text](img/image-25.png)
   3. Subnets: выбираем созданные 2 публичные подсети.
   ![alt text](img/image-26.png)
   4. Security Groups: выбираем ту же группу безопасности, что и для виртуальной машины.
   ![alt text](img/image-27.png)
   5. Listener: протокол `HTTP`, порт `80`.
   6. Default action: выбираем созданную Target Group `project-target-group`.
   ![alt text](img/image-28.png)
      > Что такое Default action и какие есть типы Default action?
      > Default action — это действие, которое выполняется, если ни одно правило Listener не сработало.
      > Типы действий: forward (отправить на Target Group), redirect (перенаправить на другой URL) и fixed-response (отправить фиксированный ответ клиенту).
   7. Нажимаем `Create load balancer`.
   ![alt text](img/image-29.png)
3. Переходим в раздел `Resource map` и убеждаемся что существуют связи между `Listeners`, `Rules` и `Target groups`.
![alt text](img/image-30.png)

### Шаг 7. Создание Auto Scaling Group

1. В разделе EC2 выбираем `Auto Scaling Groups` → `Create Auto Scaling group`.
2. Указываем следующие параметры:

   1. Название: `project-auto-scaling-group`
   ![alt text](img/image-31.png)
   2. Launch template: выбираем созданный ранее Launch Template (`project-launch-template`).
   3. Переходим в раздел `Choose instance launch options `.

      - В разделе`Network`: выбираем созданную VPC и две приватные подсети.
      ![alt text](img/image-32.png)

      > Почему для Auto Scaling Group выбираются приватные подсети?
      > Для Auto Scaling Group выбираются приватные подсети, чтобы серверы были недоступны напрямую из интернета.
      > Доступ к ним осуществляется через Load Balancer или NAT, что повышает безопасность.

   4. Availability Zone distribution: выбираем `Balanced best effort`.

      > Зачем нужна настройка: `Availability Zone distribution`?
      > vailability Zone distribution нужна, чтобы автоматически распределять инстансы по разным зонам доступности. Это повышает отказоустойчивость и защищает сервис от падения одной зоны.

   5. Переходим в раздел `Integrate with other services` и выбираем `Attach to an existing load balancer`, затем выбираем созданную Target Group (`project-target-group`).
      - Таким образом мы добавляем AutoScaling Group в Target Group нашего Load Balancer-а.
      ![alt text](img/image-33.png)
   6. Переходим в раздел `Configure group size and scaling` и укажите:

      1. Минимальное количество инстансов: `2`
      2. Максимальное количество инстансов: `4`
      3. Желаемое количество инстансов: `2`
      4. Указываем `Target tracking scaling policy` и настройте масштабирование по CPU (Average CPU utilization — `50%` / `Instance warm-up period` — `60 seconds`).
      ![alt text](img/image-34.png)
      ![alt text](img/image-35.png)

         > Что такое _Instance warm-up period_ и зачем он нужен?
         > Instance warm-up period — это время, которое Auto Scaling даёт новому инстансу, чтобы он полностью запустился и начал обслуживать трафик. Нужно, чтобы Auto Scaling не добавлял лишние инстансы, пока новые ещё не готовы принимать нагрузку.

      5. В разделе `Additional settings` поставили галочку на `Enable group metrics collection within CloudWatch`, чтобы собирать метрики Auto Scaling Group в CloudWatch. _Этот пункт позволит нам отслеживать состояние группы и её производительность_.

      ![alt text](img/image-36.png)

   7. Переходим в раздел `Review` и нажмаем `Create Auto Scaling group`.
   ![alt text](img/image-37.png)

### Шаг 8. Тестирование Application Load Balancer

1. Переходим в раздел EC2 -> `Load Balancers`, выбираем созданный Load Balancer и копируем его DNS-имя.
![alt text](img/image-38.png)
2. Вставляем DNS-имя в браузер и убедитесь, что вы видите страницу веб-сервера.
![alt text](img/image-39.png)
просто оставила подумать о своем поведении и он зараборал
![alt text](img/image-40.png)
![alt text](img/image-41.png)
3. Обновите страницу несколько раз и посмотрите на IP-адреса в ответах.
   > Какие IP-адреса вы видите и почему?
   > При обновлении страницы несколько раз видны разные IP-адреса — это потому, что Load Balancer распределяет трафик между несколькими EC2-инстансами, обеспечивая балансировку нагрузки.

### Шаг 9. Тестирование Auto Scaling

1. Переходим в CloudWatch -> `Alarms`, там созданы автоматические оповещения для Auto Scaling Group.
![alt text](img/image-42.png)
![alt text](img/image-43.png)
![alt text](img/image-44.png)
2. Выберите одно из оповещений (например, `TargetTracking-XX-AlarmHigh-...`), откройте и посмотрите на график CPU Utilization. На данный момент график низкий (около 0-1%).
![alt text](img/image-45.png)
3. Переходим в браузер и открываем 6-7 (8) вкладок со следующим адресом:

   ```
   http://<DNS-имя вашего Load Balancer-а>/load?seconds=60
   ```


4. Вернулись в CloudWatch и посмотрели на график CPU Utilization. Через несколько минут есть рост нагрузки.
![alt text](img/image-46.png)
5. Подождали 2-3 минуты, пока CloudWatch не зафиксирует высокую нагрузку и не создаст `Alarm` (показано красным цветом).
6. Переходим в раздел `EC2` -> `Instances` и смотрим на количество запущенных инстансов.

   > Какую роль в этом процессе сыграл Auto Scaling?
   > Роль Auto Scaling: автоматически добавлять или удалять инстансы в зависимости от нагрузки, обеспечивая масштабируемость и стабильную работу приложения.

   ![alt text](img/image-47.png)

### Шаг 10. Завершение работы и очистка ресурсов

- Остановили нагрузочный тест.
- Удалили созданный Load Balancer и Target Group.
- Удалили Auto Scaling Group и все запущенные EC2-инстансы.
- Удалили созданную AMI вместе с её snapshot.
- Удалили Launch Template.
- Удалили созданные VPC и подсети.

## Вывод

В ходе лабораторной работы была автоматизирована инфраструктура AWS с помощью Terraform.
Созданы VPC с публичными и приватными подсетями, EC2-инстансы, Load Balancer, Target Group и Auto Scaling Group.
Проверена работа веб-сервера, балансировка нагрузки и автоматическое масштабирование.
Все ресурсы после тестирования были удалены, что обеспечило корректное завершение работы и отсутствие лишних затрат.

## Источники

1. [Curs Балансировка нагрузки и автоматическое масштабирование. AWS ELB, EC2 AutoScaling](https://github.com/MSU-Courses/cloud-computing/tree/main/09_AWS_Load_Balancing_And_Auto_Scaling)
2. [Amazon Web Services. Amazon EC2 Auto Scaling User Guide.](https://docs.aws.amazon.com/autoscaling/ec2/userguide/what-is-amazon-ec2-auto-scaling.html)
3. [Amazon Web Services. Application Load Balancer Documentation.](https://docs.aws.amazon.com/elasticloadbalancing/latest/application/introduction.html)