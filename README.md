# Graphite module for Icinga Web 2

## General Information

This module integrates an existing [Graphite](https://graphite.readthedocs.org/en/latest/)
installation in your
[Icinga Web 2](https://www.icinga.org/icinga/screenshots/icinga-web-2/) web
frontend.

## Features

* Global overview underneath `History / Graphite` menu section
* Detail view actions: render graphs for hosts and services

## Requirements

* Icinga Web 2 v2.1.0
* Icinga 2 v2.4.0 using the new Graphite tree in ([#9461](https://dev.icinga.org/issues/9461)).
* Graphite and Graphite Web

## Prepare Icinga2

Enable the graphite feature:

    icinga2 feature enable graphite

Adjust it's configuration in `/etc/icinga2/features-enabled/graphite.conf`:

```
library "perfdata"

object GraphiteWriter "graphite" {
  host = "127.0.0.1"
  port = 2003
  enable_send_thresholds = true
}
```

And then restart Icinga2. Enabling thresholds is not a hard requirement.
However, some templates look better when they are able to render a max
values or similar.

## Installation

Just extract/clone this module to a `graphite` subfolder in your Icinga
Web 2 module path. Enable the graphite module in your Icinga Web 2 frontend
(*Configuration* > *Modules* > *graphite* > *enable*).

NB: It is best practice to install 3rd party modules into a distinct module
folder for example `/usr/share/icingaweb2/modules`. In case you don't know where this
might be please check the module path in your Icinga Web 2 configuration.

## Configuration

Open Icinga Web 2 (web UI) and navigate to *Configuration* > *Modules* >
*graphite* > *Backend*. Fill in the Graphite Web root URL
(e.g. `http://192.0.2.1:8003/`) and the HTTP basic auth credentials (if any).

You don't need any configuration in your Icinga 2 installation (e.g.
additional custom vars).

## Testdrive

Icinga 2 and Graphite feature enabled:

    icinga2 feature enable graphite

Graphite and Graphite Web running, e.g. as docker instance:

    sudo docker run -d --name graphite --restart=always -p 9090:80 -p 2003:2003 hopsoft/graphite-statsd

Icinga Web 2 with Graphite module enabled

    [graphite]
    base_url = http://localhost:9090

