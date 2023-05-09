FROM php:7.4-apache

RUN apt-get update
RUN apt-get install -y \
   git \
   vim \
   cron \
   zip \
	 libzip-dev \
	 libonig-dev \
   unzip \
   nano \
   libmemcached-dev \
   curl \
   default-mysql-client \
   wget \
   sudo \
   bash-completion \
   apt-utils \
   gnupg \
   gnupg2 \
   gnupg1

# install the PHP extensions we need
RUN set -ex; \
	\
	if command -v a2enmod; then \
		a2enmod rewrite ssl proxy proxy_http proxy_html vhost_alias xml2enc; \
	fi; \
	\
	savedAptMark="$(apt-mark showmanual)"; \
	\
	apt-get update; \
	apt-get install -y --no-install-recommends \
		libjpeg-dev \
		libpng-dev \
		libpq-dev \
	; \
	\
	docker-php-ext-configure gd --with-jpeg=/usr/include/; \
	docker-php-ext-install -j "$(nproc)" \
		gd \
		opcache \
		pdo_mysql \
		pdo_pgsql \
		zip \
		mbstring \
		pdo \
		zip \
	  bcmath \
	  mysqli \
	  sockets \
	  bcmath \
	  exif \
	  tokenizer \
	; \
	\
# reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
	apt-mark auto '.*' > /dev/null; \
	apt-mark manual $savedAptMark; \
	ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
		| awk '/=>/ { print $3 }' \
		| sort -u \
		| xargs -r dpkg-query -S \
		| cut -d: -f1 \
		| sort -u \
		| xargs -rt apt-mark manual; \
	\
	apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
	rm -rf /var/lib/apt/lists/*

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=1'; \
		echo 'opcache.fast_shutdown=1'; \
		echo 'opcache.enable_cli=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN curl https://raw.githubusercontent.com/git/git/master/contrib/completion/git-completion.bash -o ~/.git-completion.bash
RUN curl -o ~/.git-prompt.sh https://raw.githubusercontent.com/git/git/master/contrib/completion/git-prompt.sh

# Install composer
RUN curl -sSL https://getcomposer.org/installer | php  && \
   mv composer.phar /usr/local/bin/composer &&\

  # Install PHPUnit
  curl -sSL https://phar.phpunit.de/phpunit.phar -o phpunit.phar && \
    chmod +x phpunit.phar && \
       mv phpunit.phar /usr/local/bin/phpunit && \

 # Install Drupal Console
   curl https://drupalconsole.com/installer -o /usr/local/bin/drupal && \
   chmod +x /usr/local/bin/drupal && \
    echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> /root/.bashrc
RUN composer global require drush/drush:8.*
RUN ln -s $HOME/.composer/vendor/bin/drush /usr/local/bin/drush
RUN \
	mkdir -p /root/bin && \
	ln -s $HOME/.composer/vendor/bin/drush /root/bin/drush
# cleanup
RUN apt-get remove --purge -y \
   libzip-dev \
	 libonig-dev \
   libmemcached-dev
RUN \
	apt-get autoremove -y && \
	apt-get autoclean -y

# Install nodejs
RUN curl -sL https://deb.nodesource.com/setup_14.x | sudo -E bash -
RUN sudo apt-get install -y nodejs

COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY vhost.conf /etc/apache2/sites-enabled/000-default.conf
COPY update.sh /update.sh
COPY watch.sh /watch.sh
RUN \
	chmod u+x /update.sh && \
	chmod +x /watch.sh
RUN chown -R :www-data /var/www && chmod -R g+rw /var/www
WORKDIR /var/www/html
