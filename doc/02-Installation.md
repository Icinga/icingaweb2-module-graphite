# <a id="Installation"></a>Installation

## Requirements

* Icinga 2 (>= 2.4.0)
* Icinga Web 2 (>= 2.5.0)
* Graphite and Graphite Web

## Prepare Icinga 2

Enable the graphite feature:

    # icinga2 feature enable graphite

Adjust its configuration in `/etc/icinga2/features-enabled/graphite.conf`:

```
library "perfdata"

object GraphiteWriter "graphite" {
  host = "192.0.2.42"
  port = 2003
  enable_send_thresholds = true
}
```

And then restart Icinga2. Enabling thresholds is not a hard requirement.
However, some templates look better if they are able to render a max
value or similar.

## Setup the Graphite Module

Just extract/clone [this module](https://github.com/Icinga/icingaweb2-module-graphite) to a `graphite` subfolder in your Icinga Web 2
module path (for example like running this command in your module path to install the latest release of the graphite module):

    # mkdir graphite && cd graphite && \
	curl -L $(curl -s https://api.github.com/repos/Icinga/icingaweb2-module-graphite/releases/latest | jq -r ".tarball_url") | tar xz --strip=1

> **Note:**
>
> This requires the binaries `curl`, `jq` and `tar`.

Enable the graphite module in the frontend.
(*Configuration* > *Modules* > *graphite* > *enable*)

> **Note:**
>
> It is best practice to install 3rd party modules into a distinct module
> folder for example `/usr/share/icingaweb2/modules`. In case you do not
> know where this might be please check the module path in your Icinga Web 2
> configuration.
