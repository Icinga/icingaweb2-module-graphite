# <a id="Troubleshooting"></a>Troubleshooting

## Graphs missing or not shown as expected

If too less or too many graphs are shown for a host/service or the graphs don't
look as expected, debugging becomes harder if there's no obvious error message
like "Could not resolve host: example.com".

In such cases the "graphs assembling debugger" may help:

1. Navigate to the respective host/service as usual
2. Add `&graph_debug=1` to the URL
3. Inspect the log displayed under "Graphs assembling process record"

### Example

Example debug log for the host "icinga.com":

```
+ Icinga check command: 'hostalive'
+ Obscured check command: NULL
+ Applying templates for check command 'hostalive'
++ Applying template 'hostalive-rta'
+++ Fetched 1 metric(s) from 'https://example.com/metrics/expand?query=icinga2.icinga_com.host.hostalive.perfdata.rta.value'
+++ Excluded 0 metric(s)
+++ Combined 1 metric(s) to 1 chart(s)
++ Applying template 'hostalive-pl'
+++ Fetched 1 metric(s) from 'https://example.com/metrics/expand?query=icinga2.icinga_com.host.hostalive.perfdata.pl.value'
+++ Excluded 0 metric(s)
+++ Combined 1 metric(s) to 1 chart(s)
+ Applying default templates, excluding previously used metrics
++ Applying template 'default-host'
+++ Fetched 2 metric(s) from 'https://example.com/metrics/expand?query=icinga2.icinga_com.host.hostalive.perfdata.%2A.value'
+++ Excluded 2 metric(s)
+++ Combined 0 metric(s) to 0 chart(s)
++ Not applying template 'default-service'
```

The log describes how the Graphite module assembled the displayed graphs (or why
no graphs could be assembled). The plus signs indent the performed actions to
visualize their hierarchy, e.g. all actions below `Applying templates for check
command 'hostalive'` indented with more than one plus sign (until `Applying
default templates, (...)`) are sub-actions of the above one.

#### Details

At first the host's check command is being determined. Then all templates made
for that check command are applied. Finally, the default template is applied.

For each template the available Graphite metrics are fetched and combined to
graphs if possible. (See also [Templates](04-Templates.md).) The actual metrics
are not shown not to make the log too large. But they can be viewed at the shown
URLs.

Example result of the first URL:

```
{"results": ["icinga2.icinga_com.host.hostalive.perfdata.rta.value"]}
```
