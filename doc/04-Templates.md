# <a id="Templates"></a>Templates

A template defines what kind of data a graph visualizes, which kind of graph to
use and its style. Essentially this module is using templates to tell Graphite
how to render which graphs.

## Template Location

There are a bunch of templates already shipped with this module, located in
its installation path. (e.g. `/usr/share/icingaweb2/modules/graphite`)

To add additional/customized templates, place them in its configuration path.
(e.g. `/etc/icingaweb2/modules/graphite/templates`) These will either extend
the available templates or override some of them. Subfolders placed here will
also be included in the same way, while additionally extending or overriding
templates of its parent folders.

> **Note:**
>
> Hidden files and directories (with a leading dot) are ignored by this module.

## Template Structure

Templates are organized within simple INI files. However, it is perfectly valid
to define multiple templates in a single file.

The name of a section consists of two parts separated by a dot:

    [hostalive-rta.graph]

The first part is the name of the template and the second part the name of one
of the following configuration topics:

**graph**

Supports a single option called `check_command` and should be set to the name
of a Icinga 2 [check-command](https://www.icinga.com/docs/icinga2/latest/doc/03-monitoring-basics/#check-commands).  
To get multiple graphs for hosts and services with this check-command, multiple
templates can reference the same check-command.

**metrics_filters**

Define what metric to use and how many curves to display in the resulting graph.  
Each option's key represents the name of a curve. Its value the path to the
metric in Icinga 2's [graphite naming schema](https://www.icinga.com/docs/icinga2/latest/doc/14-features/#current-graphite-schema).

Curve names are used to map Graphite functions to metrics. (More on this below)
However, they are fully arbitrary and have no further meaning outside template
configurations.

A curve's metric path must begin with either the macro `$host_name_template$`
or `$service_name_template$` and is substituted with Icinga 2's prefix label.
The rest of the path is arbitrary, but to get meaningful results use a valid
path to one of the performance data metrics:

    <prefix-label>.perfdata.<perfdata-label>.<metric>

An example path which points to the metric `value` of the `rta` perfdata-label
looks as follows:

    $host_name_template$.perfdata.rta.value

To dynamically render a graph for each performance data label found, define a
macro in place for the actual perfdata-label:

    $host_name_template$.perfdata.$perfdata_label$.value

> **Note:**
>
> The name of the macro for the perfdata-label is also arbitrary. You may as
> well use a more descriptive name such as `$disk$` for the disk check.

**urlparams**

Allows to define additional URL parameters to be passed to Graphite's render
API.

Each option represents a single parameter's name and value. A list of all
supported parameters can be found [here](https://graphite.readthedocs.io/en/latest/render_api.html#graph-parameters).

If you have used a macro for the curve's perfdata-label you may utilize it
here as well:

    title = "Disk usage on $disk$"

**functions**

Allows to define Graphite functions which are applied to the metric of a
specific curve on the graph.

Each option's key must match a curve's name in order to apply the function
to the curve's metric. A list of all supported functions can be found [here](https://graphite.readthedocs.io/en/latest/functions.html#functions).

The metric in question can be referenced in the function call using the macro
`$metric$` as shown in the following example:

    alias(color(scale($metric$, 1000), '#1a7dd7'), 'Round trip time (ms)')

## Template Example

The configuration examples used in this document are borrowed from the template
for the `hostalive` check-command:

```ini
[hostalive-rta.graph]
check_command = "hostalive"

[hostalive-rta.metrics_filters]
rta.value = "$host_name_template$.perfdata.rta.value"

[hostalive-rta.urlparams]
areaAlpha = "0.5"
areaMode = "all"
min = "0"
yUnitSystem = "none"

[hostalive-rta.functions]
rta.value = "alias(color(scale($metric$, 1000), '#1a7dd7'), 'Round trip time (ms)')"


[hostalive-pl.graph]
check_command = "hostalive"

[hostalive-pl.metrics_filters]
pl.value = "$host_name_template$.perfdata.pl.value"

[hostalive-pl.urlparams]
areaAlpha = "0.5"
areaMode = "all"
min = "0"
yUnitSystem = "none"

[hostalive-pl.functions]
pl.value = "alias(color($metric$, '#1a7dd7'), 'Packet loss (%)')"
```
