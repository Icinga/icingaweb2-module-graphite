# Development

This module provides a CLI command for demonstrating
graph templates (useful for developing them):

```bash
icingacli graphite icinga2 config
```

It generates Icinga 2 config based on the present graph templates.
With this config Icinga will (also) "monitor" dummy services yielding random
perfdata as expected by the graph templates.

I. e.: If that Icinga is also writing to the Graphite that is
read by this module, you'll get dummy graphs for all templates.
