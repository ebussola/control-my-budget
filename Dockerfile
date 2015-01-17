FROM debian:wheezy

RUN apt-get upgrade -y
RUN apt-get update
RUN apt-get install -y wget ca-certificates

RUN wget -qO - http://www.dotdeb.org/dotdeb.gpg | apt-key add -
ADD dotdeb.list /etc/apt/sources.list.d/doteb.list

RUN apt-get update
RUN apt-get install -y php5 php5-imap php5-intl php5-curl php5-sqlite php5-tidy

RUN php -r "readfile('https://getcomposer.org/installer');" | php
RUN mv composer.phar /usr/local/bin/composer

RUN echo "date.timezone = America/Sao_Paulo" > /etc/php5/cli/conf.d/20-timezone.ini