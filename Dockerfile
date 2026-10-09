FROM drupal:10-apache

# Install required tools: git, unzip for composer, and mysql client for DB sync
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /opt/drupal

# Copy php custom configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/docker-php-drupal.ini

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy application files
COPY . /opt/drupal/

# Install composer production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configure Apache DocumentRoot to /opt/drupal/web
ENV APACHE_DOCUMENT_ROOT=/opt/drupal/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure Apache AllowOverride for clean URLs
RUN echo '<Directory /opt/drupal/web>\n    AllowOverride All\n    Require all granted\n</Directory>' >> /etc/apache2/apache2.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Prepare files directory and permissions
RUN mkdir -p /opt/drupal/web/sites/default/files && \
    chown -R www-data:www-data /opt/drupal/web/sites/default/files

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
