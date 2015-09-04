# Graphite module for Icinga Web 2

## General Information

This module integrates an existing [Graphite](https://graphite.readthedocs.org/en/latest/)
installation in your
[Icinga Web 2](https://www.icinga.org/icinga/screenshots/icinga-web-2/) web
frontend.


> **Note**
>
> This is a showcase module and to be considered an unsupported prototype
> unless explicitely stated otherwise.
>
> It depends on Icinga 2 v2.4.0 which is currently in depelopment ([#9461](https://dev.icinga.org/issues/9461)).

## Installation

Just extract this to your Icinga Web 2 module folder. Enable the graphite
module in your Icinga Web 2 frontend
(Configuration -> Modules -> graphite -> enable) and it should work out of
the box.

NB: It is best practice to install 3rd party modules into a distinct module
folder like /usr/share/icingaweb2/modules. In case you don't know where this
might be please check the module path in your Icinga Web 2 configuration.

## Configuration

Copy the sample configuration to `/etc/icingaweb2/modules` like so:

    mkdir /etc/icingaweb2/modules/graphite
    cp -rv sample-config/* /etc/icingaweb2/modules/graphite/


## TODO

* Web based configuration
* Additional filters
* Tree integration for detail views


## Testdrive

Icinga 2 and Graphite feature enabled:

    icinga2 feature enable graphite

Graphite and Graphite Web running, e.g. as docker instance:

    sudo docker run -d --name graphite --restart=always -p 9090:80 -p 2003:2003 hopsoft/graphite-statsd

Icinga Web 2 with Graphite module enabled

    [global]
    web_url = http://localhost:9090
    host_pattern = icinga2.$hostname


