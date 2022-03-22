# <a id="Installation"></a>Installation

## Requirements

* PHP (>= 7.2)
* Icinga 2 (>= 2.4.0)
* Icinga Web 2 (>= 2.9)
* Graphite and Graphite Web

## Prepare Icinga 2

Enable the graphite feature:

```
# icinga2 feature enable graphite
```

Adjust its configuration in `/etc/icinga2/features-enabled/graphite.conf`:

```
object GraphiteWriter "graphite" {
  host = "192.0.2.42"
  port = 2003
  enable_send_thresholds = true
}
```

And then restart Icinga2. Enabling thresholds is not a hard requirement.
However, some templates look better if they are able to render a max
value or similar.


## Install the Graphite Module

Install it [like any other module](https://icinga.com/docs/icinga-web-2/latest/doc/08-Modules/#installation).
Use `graphite` as name.


## Further reading

* [Configuration](03-Configuration.md)
* [Templates](04-Templates.md)
* [Troubleshooting](05-Troubleshooting.md)
