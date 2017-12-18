# <a id="Configuration"></a>Configuration

## Basics

Open up the Icinga Web 2 frontend and navigate to:

    Configuration > Modules > graphite > Backend

Enter the Graphite Web URL. (e.g. `https://192.0.2.42:8003/`)

The HTTP basic authentication credentials are only required
if your Graphite Web is protected by such a mechanism.

## Advanced

Open up the Icinga Web 2 frontend and navigate to:

    Configuration > Modules > graphite > Advanced

The settings *Host name template* and *Service name template* both are only
required if you are using a different naming schema than the default Icinga 2
is using. (As outlined [here](https://www.icinga.com/docs/icinga2/latest/doc/14-features/#current-graphite-schema))

The setting *Obscured check command custom variable* is only required if there
are wrapped check commands (see below) and the "actual" check command is stored
in another custom variable than `check_command`.

## Wrapped check commands

If a monitored object is checked remotely and not via an Icinga 2 agent, but
e.g. by check_by_ssh or check_nrpe, the monitored object's effective check
command becomes by_ssh or nrpe respectively. This breaks the respective
monitored objects' graphs as graph templates are applied to monitored objects
via their check commands. (They fall back to the default template.)

To make the respective graphs working as expected you have to tell the
monitored object's "actual" check command by setting its custom variable
`check_command`, e.g.:

```
apply Service "by_ssh-disk" {
  import "generic-service"
  check_command = "by_ssh"
  vars.by_ssh_address = "192.0.2.1"
  vars.by_ssh_command = "/usr/lib64/nagios/plugins/check_disk -w 20 -c 10"
  vars.check_command = "disk"    // <= HERE
  assign where host.name == NodeName
}
```
