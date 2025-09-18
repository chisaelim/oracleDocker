# Use a simpler approach with Ubuntu base
FROM ubuntu:22.04

# Prevent interactive prompts during package installation
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies including Apache, PHP, and Oracle prerequisites
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-dev \
    php8.1-cli \
    php8.1-common \
    php8.1-mysql \
    php8.1-zip \
    php8.1-gd \
    php8.1-mbstring \
    php8.1-curl \
    php8.1-xml \
    php8.1-bcmath \
    libapache2-mod-php8.1 \
    libaio1 \
    libaio-dev \
    unzip \
    wget \
    curl \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite ssl headers php8.1

# Install Oracle Instant Client
RUN mkdir -p /opt/oracle && cd /opt/oracle

# Download Oracle Instant Client using working URLs
WORKDIR /tmp
RUN wget -O instantclient-basic.zip "https://download.oracle.com/otn_software/linux/instantclient/1919000/instantclient-basic-linux.x64-19.19.0.0.0dbru.zip" \
    && wget -O instantclient-devel.zip "https://download.oracle.com/otn_software/linux/instantclient/1919000/instantclient-sdk-linux.x64-19.19.0.0.0dbru.zip" \
    && unzip instantclient-basic.zip -d /opt/oracle \
    && unzip instantclient-devel.zip -d /opt/oracle \
    && rm -f *.zip \
    && mv /opt/oracle/instantclient_19_19 /opt/oracle/instantclient

# Set Oracle environment variables
ENV ORACLE_HOME=/opt/oracle/instantclient
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient:$LD_LIBRARY_PATH
ENV PATH=/opt/oracle/instantclient:$PATH

# Update shared library cache
RUN echo "/opt/oracle/instantclient" > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig

# Install PHP development tools needed for compiling extensions
RUN apt-get update && apt-get install -y \
    php8.1-dev \
    re2c \
    && rm -rf /var/lib/apt/lists/*

# Download and compile OCI8 extension
WORKDIR /tmp
RUN wget https://pecl.php.net/get/oci8-3.2.1.tgz \
    && tar -xzf oci8-3.2.1.tgz \
    && cd oci8-3.2.1 \
    && phpize \
    && ./configure --with-oci8=instantclient,/opt/oracle/instantclient \
    && make && make install \
    && echo "extension=oci8.so" > /etc/php/8.1/apache2/conf.d/20-oci8.ini \
    && echo "extension=oci8.so" > /etc/php/8.1/cli/conf.d/20-oci8.ini

# Note: PDO_OCI is not easily available, so we'll use OCI8 extension instead
# The application will be updated to use OCI8 functions

# Clean up
RUN rm -rf /tmp/oci8-3.2.1*

# Install Composer for potential future use
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure PHP
RUN echo "memory_limit = 256M" > /etc/php/8.1/apache2/conf.d/99-custom.ini \
    && echo "upload_max_filesize = 50M" >> /etc/php/8.1/apache2/conf.d/99-custom.ini \
    && echo "post_max_size = 50M" >> /etc/php/8.1/apache2/conf.d/99-custom.ini \
    && echo "max_execution_time = 300" >> /etc/php/8.1/apache2/conf.d/99-custom.ini \
    && echo "date.timezone = UTC" >> /etc/php/8.1/apache2/conf.d/99-custom.ini

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Create Apache virtual host configuration
COPY apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY public/ /var/www/html/
COPY config/ /var/www/html/config/
COPY includes/ /var/www/html/includes/
COPY assets/ /var/www/html/assets/

# Remove default Apache index.html and set permissions
RUN rm -f /var/www/html/index.html \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Expose port
EXPOSE 80

# Start Apache in foreground
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]