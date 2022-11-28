<!-- {% if index %} -->
# Installing Icinga Web Graphite Integration

It is recommended to use prebuilt packages
for all supported platforms from our official release repository.
Of course [Icinga Web](https://icinga.com/docs/icinga-web) itself
is required to run its Graphite integration.
The latter uses Graphite Web, so that is required as well.
If they are not already set up, it is best to do this first.

The following steps will guide you through installing
and setting up Icinga Web Graphite Integration.
<!-- {% else %} -->
<!-- {% if not icingaDocs %} -->

## Installing the Package

If the [repository](https://packages.icinga.com) is not configured yet, please add it first.
Then use your distribution's package manager to install the `icinga-graphite` package
or install [from source](02-Installation.md.d/From-Source.md).
<!-- {% endif %} --><!-- {# end if not icingaDocs #} -->

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

## Configuring the Icinga Web Graphite Integration

For required additional steps see the [Configuration](03-Configuration.md) chapter.
<!-- {% endif %} --><!-- {# end else if index #} -->
