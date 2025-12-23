# оф образ PHP с CLI без Apache/Nginx
FROM php:8.3-cli

# необходимые системные зависимости
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install zip pdo pdo_sqlite gd

# composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# рабочая директория внутри контейнера
WORKDIR /app

# копия composer.json и composer.lock 
COPY composer.json composer.lock* ./

# PHP-зависимости
RUN composer install --no-dev --optimize-autoloader

# копия всего проекта
COPY . .

# папка для бд и загрузок
RUN mkdir -p storage/uploads

# права на запись 
RUN chmod -R 777 storage/

# порт для совместимости
EXPOSE 8080

# запуск встроенного PHP-сервера
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public/"]