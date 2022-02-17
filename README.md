# phpSLDt
RESTful Web Service для работы с однолинейными электрическими схемами групповых сетей по [ГОСТ 21.613-2014](https://docs.cntd.ru/document/1200115056) ([Приложение А](https://docs.cntd.ru/document/1200115056?marker=7EK0KJ), Рисунок А.4 _"Пример выполнения принципиальной схемы групповой сети при использовании систем автоматизированного проектирования или информационного моделирования зданий, сооружений"_).

- [Requirements](#requirements)  
- [Getting Started](#getting-started)  
   - [Prerequisites](#prerequisites)  
   - [Installing](#installing)  
- [Running the tests](#running-the-tests)  
- [Usage]()
- [Deployment](#deployment)  
- [Built With](#built-with)  
- [Versioning](#versioning)
- [Authors](#authors)
- [License](#licence)

## Requirements
* Mariadb 10.3 or higher
* PHP 7.2 or higher,
* Unzip,
* PHP extensions:
  - pdo_mysql
  - curl
  - gd

## Getting Started

Эти инструкции помогут вам запустить копию проекта для разработки и тестирования. Заметки о том, как развернуть проект в действующей системе, см. в разделе [Deployment](#deployment).

### Prerequisites

Для установки phpSLDt вам потребуется:
* Web-сервер с поддержкой PHP,
* СУБД MariaDB,
* Пакетный менеджер Composer,
* Система управления версиями git.

В [OpenBSD 7.0](https://www.openbsd.org/70.html) установка и настройка всего необходимого может выглядеть следующим образом.

#### Установка MariaDB


```
# pkg_add mariadb-server
```

```
# rcctl enable mysqld
# mysql_install_db
# rcctl start mysqld
```
Затем
```
# mysql_secure_installation
```

#### Установка PHP с необходимыми расширениями

```
# pkg_add php php-pdo_mysql php-curl php-gd
```

В файле `/etc/php-7.4.ini`раскоментируйте или добавьте следующие строки

```
extension=gd
extension=curl
extension=pdo_mysql
```

После чего

```
# rcctl enable php74_fpm
# rcctl start php74_fpm
```

#### Настройка Web-сервера [httpd](https://bsd.plumbing/)

Отредактируйте или создайте `/etc/httpd.conf`

```
# [ SERVERS ]
server "default" {
    listen on * port 80
    root "/htdocs"
    directory { no auto index }
    
    location "*.php" {
    	fastcgi socket "/run/php-fpm.sock"
    }
    
    location match "phpSLDt/api/(.*)" {
    	request rewrite "/phpSLDt/index.php?endpoint=%1&$QUERY_STRING"
    }
}

# [ TYPES ]
types {
    include "/usr/share/misc/mime.types"
}
```
Затем выполните

```
# rcctl enable httpd
# rcctl start httpd
httpd(ok)
```

#### Установка Composer, Git и Unzip

```
# pkg_add composer git unzip
```


### Installing

Клонируем проект phpSLDt
```
# cd /var/www/htdocs
# git clone https://github.com/ensoelectric/phpSLDt.git phpSLDt
# cd phpSLDt
```

Создаем базу данных и добавляем пользователей
```
# mysql -uroot -p < install.sql
Enter password:
#
```

Устанавливаем зависимости
```
# composer update
```

Задаем имя пользователя и пароль в секции `development` файлa _phinx.php_. Запускаем миграции.

```
# ./vendor/bin/phinx migrate -e development
```

Заполняем таблицы тестовыми данными

```
# ./vendor/bin/phinx seed:run -e development
```

Добавляем пользователей в БД

```
# mysql -uroot -p < users.sql
Enter password:
```



## Running the tests

```
# ./vendor/bin/codecept run
Codeception PHP Testing Framework v4.1.29
Powered by PHPUnit 9.5.13 by Sebastian Bergmann and contributors.

Api Tests (10)
✔ CORSCest: Get response to preflight request cors (0.05s)
✔ DiagramsCest: Get allow methods test (0.09s)
✔ DiagramsCest: Set accept header test (0.07s)
✔ DiagramsCest: Get all diagrams test (0.09s)
✔ DiagramsCest: Get the diagram test (0.03s)
✔ DiagramsCest: Not found diagram test (0.05s)
✔ DiagramsCest: Create new diagram precondition failed test (0.05s)
✔ DiagramsCest: Create new diagram unprocessable test (0.02s)
✔ DiagramsCest: Create new diagram test (0.04s)
✔ DiagramsCest: Update diagram test (0.03s)


Time: 00:00.620, Memory: 12.00 MB

OK (10 tests, 35 assertions)

```

## USAGE

API описано в файле `openapi_phpSLDt.yaml` в форме **OpenAPI Specification**. Импортируйте данный файл, например, в [postman](https://www.postman.com/) или [swagger](https://swagger.io/).

## Deployment

- Обязательно настройте HTTPS. 
- Удалите пользователей по-умолчанию и создайте новых (см. `users.sql`)

## Built With

* [Phinx](https://phinx.org/) - PHP Database Migrations
* [Composer](https://getcomposer.org/) - A Dependency Manager for PHP
* [Codeception](https://codeception.com/) - PHP unit testing

## Versioning

Стараюсь придерживаться [cемантического версионирования](http://semver.org/).

## Authors

* **Artirm Pletnev** - [artirm.pletnev@gmail.com](mailto:artirm.pletnev@gmail.com), [github](https://github.com/ensoelectric)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
