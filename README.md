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

## Features

* Global overview underneath `Reporting` menu section
* Detail view actions: Jump from host/service detail view directly into Graphite Web


## Requirements

* Icinga Web 2 v2.0.0 (or git snapshot younger than 5.9.2015 using namespaced controllers)
* Icinga 2 v2.4.0 using the new Graphite tree in ([#9461](https://dev.icinga.org/issues/9461)).
* Graphite and Graphite Web


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

Change permissions (Note: Change `apache` to the user your
webserver is running as). Example for RHEL/CentOS/Fedora:

    chown -R apache:apache /etc/icingaweb2/modules/graphite

Edit `/etc/icingaweb2/modules/graphite/config.ini` and set `web_url`
to the Graphite web host.

    [global]
    web_url = http://my.graphite.web

Modify the `host_pattern` attribute to match your current tree.

Example for Icinga 2 v2.4.x:

    host_pattern = icinga2.$hostname

You don't need any configuration in your Icinga 2 installation (e.g.
additional custom vars).

### Templates

There are several sample templates shipped with this module.

When using the Icinga 2 v2.4.x+ tree, you may filter based on

* host name
* service name
* check command name (similar to other graphing solution such as PNP)



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

