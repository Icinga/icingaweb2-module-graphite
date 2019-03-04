FROM debian:9 as icingaweb2-module-graphite
SHELL ["/bin/bash", "-exo", "pipefail", "-c"]

ADD . /icingaweb2-module-graphite

RUN rm -rf /icingaweb2-module-graphite/.* || true


FROM debian:9
SHELL ["/bin/bash", "-exo", "pipefail", "-c"]

RUN apt-get update ;\
	DEBIAN_FRONTEND=noninteractive apt-get install --no-install-{recommends,suggests} -y \
		apt-transport-https gnupg2 dirmngr ca-certificates ;\
	apt-get clean ;\
	rm -vrf /var/lib/apt/lists/* ;\
	apt-key adv --fetch-keys 'https://packages.icinga.com/icinga.key' ;\
	DEBIAN_FRONTEND=noninteractive apt-get purge -y gnupg2 dirmngr ;\
	DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y

ADD .docker/apt-ext.list /etc/apt/sources.list.d/ext.list

RUN apt-get update ;\
	DEBIAN_FRONTEND=noninteractive apt-get install --no-install-recommends --no-install-suggests -y \
	icinga2-{bin,ido-mysql} dbconfig-no-thanks mariadb-server \
	apache2 icingaweb2{,-module-monitoring} icingacli php7.0-{intl,imagick,mysql,curl} locales \
	gcc libapache2-mod-wsgi libcairo2 libffi-dev python{,-dev} virtualenv ;\
	apt-get clean ;\
	rm -vrf /var/lib/apt/lists/* /etc/icinga2/conf.d/* /etc/icingaweb2/* ;\
	a2dissite 000-default ;\
	perl -pi -e 's~//~~ if /const NodeName/' /etc/icinga2/constants.conf ;\
	perl -pi -e 'if (!%locales) { %locales = (); for my $d ("", "/modules/monitoring") { for my $f (glob "/usr/share/icingaweb2${d}/application/locale/*_*") { if ($f =~ m~/(\w+)$~) { $locales{$1} = undef } } } } s/^# ?// if (/ UTF-8$/ && /^# (\w+)/ && exists $locales{$1})' /etc/locale.gen ;\
	perl -e 'print "object GraphiteWriter \"graphite\" {\n  enable_send_thresholds = true\n  enable_send_metadata = true\n}\n"' > /etc/icinga2/features-available/graphite.conf

RUN adduser --system --group --home /opt/graphite/home graphite ;\
	chown graphite:graphite /opt/graphite ;\
	su -ls /bin/bash -c 'set -exo pipefail; /usr/bin/virtualenv /opt/graphite; . /opt/graphite/bin/activate; export PYTHONPATH=/opt/graphite/lib/:/opt/graphite/webapp/; /opt/graphite/bin/pip install --no-binary=:all: https://github.com/graphite-project/whisper/tarball/87ae6def1bece7e079d49a61ac8d09c6ebfe4e96; /opt/graphite/bin/pip install --no-binary=:all: https://github.com/graphite-project/carbon/tarball/f36da0f77aaf83a61f9880dec7abbf5c14a7d2bb; /opt/graphite/bin/pip install --no-binary=:all: https://github.com/graphite-project/graphite-web/tarball/53d96432b6ba1c30797405dff97ba01af009cb25; for f in /opt/graphite/conf/*.example; do ln -vs "$f" "/opt/graphite/conf/$(basename "$f" .example)"; done; /opt/graphite/bin/django-admin migrate --settings=graphite.settings' graphite

COPY --from=grandmaster/nosu:latest /usr/local/bin/nosu /usr/local/bin/nosu

COPY --from=icingaweb2-module-graphite /icingaweb2-module-graphite /usr/share/icingaweb2/modules/graphite

RUN set -a; . /etc/default/icinga2; set +a ;\
	/usr/lib/icinga2/prepare-dirs /etc/default/icinga2

RUN install -m 755 -o mysql -g root -d /var/run/mysqld

RUN mysqld -u mysql & \
	MYSQLD_PID="$!" ;\
	while ! mysql <<<''; do sleep 1; done ;\
	mysql <<<"CREATE DATABASE icinga2; USE icinga2; $(< /usr/share/icinga2-ido-mysql/schema/mysql.sql) GRANT ALL ON icinga2.* TO nagios@localhost IDENTIFIED VIA unix_socket; GRANT SELECT ON icinga2.* TO 'www-data'@localhost IDENTIFIED VIA unix_socket;" ;\
	kill "$MYSQLD_PID" ;\
	while test -e "/proc/$MYSQLD_PID"; do sleep 1; done

COPY .docker/icinga2-ido.conf /etc/icinga2/features-available/ido-mysql.conf

RUN set -a; . /etc/default/icinga2; set +a ;\
	for f in command graphite ido-mysql; do icinga2 feature enable $f; done

COPY .docker/php-icingaweb2.ini /etc/php/7.0/apache2/conf.d/99-icingaweb2.ini
ADD --chown=www-data:icingaweb2 .docker/icingaweb2 /etc/icingaweb2

RUN install -o www-data -g icingaweb2 -m 02770 -d /etc/icingaweb2/enabledModules ;\
	ln -vs /usr/share/icingaweb2/modules/monitoring /etc/icingaweb2/enabledModules/monitoring ;\
	ln -vs /usr/share/icingaweb2/modules/graphite /etc/icingaweb2/enabledModules/graphite ;\
	locale-gen -j 4

COPY .docker/apache2-site.conf /etc/apache2/sites-available/icingaweb2.conf
RUN a2enmod proxy; a2enmod proxy_http; a2ensite icingaweb2

COPY --from=ochinchina/supervisord:latest /usr/local/bin/supervisord /usr/local/bin/supervisord
COPY .docker/supervisord.conf /etc/

CMD icingacli graphite icinga2 config >/etc/icinga2/conf.d/graphite.conf ;\
	exec supervisord -c /etc/supervisord.conf
