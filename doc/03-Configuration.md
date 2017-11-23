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
