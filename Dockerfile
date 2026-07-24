FROM php:8.3-apache

# Cài đặt các thư viện hệ thống và PHP extensions cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql gd zip

# Bật Apache mod_rewrite cho Laravel
RUN a2enmod rewrite

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cài đặt Node.js (cần cho Vite và Vue admin)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Đặt thư mục làm việc
WORKDIR /var/www/html

# Copy toàn bộ mã nguồn vào container
COPY . /var/www/html

# Cấu hình DocumentRoot của Apache trỏ vào thư mục public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Cài đặt dependencies PHP (Laravel)
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
RUN composer install --optimize-autoloader --ignore-platform-reqs

# Build Frontend (nếu dự án chính có dùng Mix/Vite)
RUN npm install && npm run build

# Cung cấp cờ môi trường để Vite biên dịch đúng đường dẫn cho production
ENV IS_DOCKER_BUILD=1

# Build Admin Vue (nếu có thư mục admin-frontend)
RUN if [ -d "admin-frontend" ]; then cd admin-frontend && npm install && npm run build; fi

# Cấp quyền cho storage và bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Cấu hình Apache listen port theo biến môi trường PORT (của Render)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Copy file script khởi động
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
