# An official PHP image compatible with ARM architecture
FROM arm32v7/php:8.0-apache

# Setting the working directory
WORKDIR /var/www/html

# Copying the PHP project files to the working directory
COPY . /var/www/html

# Installing necessary PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enabling Apache mod_rewrite
RUN a2enmod rewrite

# Exposing port 80
EXPOSE 80

# Starting Apache in the foreground
CMD ["apache2-foreground"]
