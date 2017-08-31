FROM php:7.0-cli

# Basic packages
RUN apt-get update && apt-get install -y \
  wget \
  git \
  python-pip \
  python-dev

RUN pip install autobahntestsuite \
  && pip install --upgrade six pyasn1

# Install composer
RUN curl -k -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer 

WORKDIR /usr/src/woketo
