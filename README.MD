# 7 Colors [Laravel]
Данная игра была сделана с целью участия в цифровой олимпиаде "[Волга-IT’XXI](https://www.volga-it.org/)"
Ссылка на само задание: [Файл PDF](https://www.volga-it.org/wp-content/plugins/wp-olymp/files/eba9ca7c9e68c9d1f027f11bc847856d.pdf)
## Установка (Docker)
```
git clone https://github.com/Ponywka/7colors-php
docker-compose up --build
```
После запуска Docker-контейнера игра будет доступна по адресу http://127.0.0.1:8000/
## Установка (Локально)
Необходимые компоненты (Linux):
* Настроенный Apache + PHP + MySQL
* [Composer](https://getcomposer.org/)

Необходимые компоненты (Windows):
* [OpenServer](https://ospanel.io/)

```
git clone https://github.com/Ponywka/7colors-php
composer install
cp ./src/.env.example ./src/.env
```
Далее необходимо в файле src/.env ввести свои данные от базы данных в полях `DB_*`
```
php artisan migrate
```
Далее необходимо отконфигурировать Apache на папку с `./src/public`