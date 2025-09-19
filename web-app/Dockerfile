
# Minimal Dockerfile for PHP web app with Oracle connectivity
FROM ubuntu:22.04

# Avoid interactive prompts
ENV DEBIAN_FRONTEND=noninteractive

# Install Apache, PHP, and Oracle Instant Client dependencies
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-dev \
    libapache2-mod-php8.1 \
    libaio1 \
    unzip \
    wget \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Enable required Apache modules
RUN a2enmod rewrite php8.1

# Install Oracle Instant Client (for PHP OCI8)
WORKDIR /tmp
RUN wget -O instantclient-basic.zip "https://download.oracle.com/otn_software/linux/instantclient/1919000/instantclient-basic-linux.x64-19.19.0.0.0dbru.zip" \
    && unzip instantclient-basic.zip -d /opt/oracle \
    && rm -f instantclient-basic.zip \
    && mv /opt/oracle/instantclient_19_19 /opt/oracle/instantclient

# Set Oracle environment variables
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient
ENV PATH=/opt/oracle/instantclient:$PATH

# Update shared library cache
RUN echo "/opt/oracle/instantclient" > /etc/ld.so.conf.d/oracle-instantclient.conf && ldconfig

# Install and enable OCI8 PHP extension
RUN wget https://pecl.php.net/get/oci8-3.2.1.tgz \
    && tar -xzf oci8-3.2.1.tgz \
    && cd oci8-3.2.1 \
    && phpize \
    && ./configure --with-oci8=instantclient,/opt/oracle/instantclient \
    && make && make install \
    && echo "extension=oci8.so" > /etc/php/8.1/apache2/conf.d/20-oci8.ini \
    && echo "extension=oci8.so" > /etc/php/8.1/cli/conf.d/20-oci8.ini \
    && cd .. && rm -rf oci8-3.2.1*

# Copy Apache virtual host config
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Copy application files
WORKDIR /var/www/html
COPY public/ /var/www/html/
COPY config/ /var/www/html/config/
COPY includes/ /var/www/html/includes/
COPY assets/ /var/www/html/assets/

# Set permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Health check for Apache
HEALTHCHECK CMD curl -f http://localhost/ || exit 1

# Expose HTTP port
EXPOSE 80

# Start Apache in foreground
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]