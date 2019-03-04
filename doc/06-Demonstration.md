# <a id="Demonstration"></a>Demonstration

This repository ships a [Dockerfile](../Dockerfile.demo) for demonstrating
and/or developing this module (but not for using it in production).

Build:

```bash
docker build -t icingaweb2-module-graphite-demo -f Dockerfile.demo .
```

Run:

```bash
docker run -itp 8080:80 icingaweb2-module-graphite-demo
```

The container serves an Icinga Web 2 with this module and all dependencies
at http://localhost:8080/icingaweb2 and Graphite Web at http://localhost:8080.

Icinga monitors dummy services yielding random perfdata
as expected by the shipped graph templates.

Use the container for development without re-building:

```bash
docker run -itp 8080:80 -v "$(pwd):/usr/share/icingaweb2/modules/graphite" icingaweb2-module-graphite-demo
```

Preserve graphs:

```bash
docker run -itp 8080:80 -v "$(pwd)/.whisper:/opt/graphite/storage/whisper" icingaweb2-module-graphite-demo
```
