FROM ubuntu:14.04.4
MAINTAINER Autobot <ubclbry-a-autobot@mail.ubc.ca>

# Surpress Upstart errors/warning
RUN dpkg-divert --local --rename --add /sbin/initctl
RUN ln -sf /bin/true /sbin/initctl

# Let the conatiner know that there is no tty
ENV DEBIAN_FRONTEND noninteractive

# Update base image, add sources for latest nginx, and install software requirements
RUN apt-get update && \
apt-get install -y software-properties-common && \
nginx=stable && \
add-apt-repository ppa:nginx/$nginx && \
apt-get update && \
apt-get upgrade -y && \
BUILD_PACKAGES="htop git curl nginx vim pdftk memcached php5-apcu php5-cli php5-fpm php5-curl php5-mysqlnd php5-pgsql php5-memcached php5-gd php5-intl php5-recode php5-tidy php5-xmlrpc php5-xsl php5-mcrypt" && \
apt-get -y install $BUILD_PACKAGES && \
apt-get remove --purge -y software-properties-common && \
apt-get autoremove -y && \
apt-get clean && \
apt-get autoclean && \
rm -rf /usr/share/man/?? && \
rm -rf /usr/share/man/??_* && \
rm -Rf /etc/nginx/conf.d/* && \
rm -Rf /etc/nginx/sites-enabled/default && \
rm -Rf /etc/nginx/sites-available/default && \
mkdir -p /etc/nginx/ssl/ && \
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer  && \
php5enmod mcrypt

# add the site definition
COPY resources/nginx/sites-available/cr-staff /etc/nginx/sites-available/cr-staff
COPY resources/nginx/nginx.conf /etc/nginx/nginx.conf

# enable the site
RUN ln -s /etc/nginx/sites-available/cr-staff /etc/nginx/sites-enabled/cr-staff


# Copy the working directory
COPY . /usr/local/cr-staff

# Make these available on the host, so we can grep the log files without having to enter the container
VOLUME ["/var/log/nginx", "/var/log/cr-staff"]

# Expose the 10 ports applicable to this application
EXPOSE 80
