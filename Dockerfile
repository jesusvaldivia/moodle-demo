FROM alpine:3.10 
LABEL Maintainer="Jesus Valdivia <jvaldivia@bitorical.com>"

# Install packages
RUN apk --no-cache add php7 php7-fpm php7-mysqli php7-json php7-openssl php7-curl \
    php7-zlib php7-xml php7-phar php7-intl php7-dom php7-xmlreader php7-xmlwriter php7-ctype php7-session \
    graphviz aspell ghostscript clamav php7-pspell php7-gd php7-xmlrpc php7-ldap php7-zip php7-soap php7-mbstring php7-iconv \
    php7-tokenizer php7-simplexml php7-fileinfo php7-opcache nfs-utils tzdata php7-pecl-redis supervisor curl 
    #nginx 

# Install bash for linux
RUN apk add --no-cache bash

#Install locale
ENV MUSL_LOCPATH="/usr/share/i18n/locales/musl"
COPY musl-locales /musl-locales
RUN apk --no-cache add libintl && \
	apk --no-cache --virtual .locale_build add cmake make musl-dev gcc gettext-dev git && \
	cd musl-locales && cmake -DLOCALE_PROFILE=OFF -DCMAKE_INSTALL_PREFIX:PATH=/usr . && make && make install && \
	cd .. && rm -r musl-locales && \
	apk del .locale_build

# Copy localtime
RUN cp /usr/share/zoneinfo/America/Lima /etc/localtime
# set localtime
RUN echo "America/Lima" >  /etc/timezone
# Set the lang
ENV LANG=es_ES.UTF-8 \
    LANGUAGE=es_Es.UTF-8 \
    LC_CTYPE=es_ES.UTF-8 \
    LC_ALL=es_ES.UTF-8

# Configure nginx
# COPY config/nginx.conf /etc/nginx/nginx.conf
# Remove default server definition
# RUN rm /etc/nginx/conf.d/default.conf

# Configure PHP-FPM
COPY config/fpm-pool.conf /etc/php7/php-fpm.d/www.conf
COPY config/php.ini /etc/php7/conf.d/custom.ini

# Configure supervisord
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
 RUN chown -R nobody.nobody /run
#  chown -R nobody.nobody /var/lib/nginx && \
#  chown -R nobody.nobody /var/tmp/nginx && \
#  chown -R nobody.nobody /var/log/nginx

# Setup document root
RUN mkdir -p /var/www/html \
    mkdir /var/moodledata \
    mkdir /var/localcache

RUN chown -R nobody.nobody /var/localcache

# Switch to use a non-root user from here on
USER nobody

# Add application
WORKDIR /var/www/html
COPY --chown=nobody src/ /var/www/html/

# Expose the port nginx is reachable on
# EXPOSE 8080

# Expose the port fpm 
EXPOSE 9000

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]