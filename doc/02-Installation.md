# <a id="Installation"></a>Installation

## Requirements

* Icinga 2 (>= 2.4.0)
* Icinga Web 2 (>= 2.5.0)
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

As with any Icinga Web 2 module, installation is pretty straight-forward. In
case you're installing it from source all you have to do is to drop the graphite
module in one of your module paths. You can examine (and set) the module path(s)
in `Configuration / Application`. In a typical environment you'll probably drop the
module to `/usr/share/icingaweb2/modules/graphite`. Please note that the graphitey
name MUST be `graphite` and not `icingaweb2-module-graphite` or anything else.

### Installation from release tarball

Download the [latest version](https://github.com/Icinga/icingaweb2-module-graphite/releases)
and extract it to a folder named `graphite` in one of your Icinga Web 2 module path graphiteies.

You might want to use a script as follows for this task:

    ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
    REPO_URL="https://github.com/icinga/icingaweb2-module-graphite"
    TARGET_DIR="${ICINGAWEB_MODULEPATH}/graphite"
    MODULE_VERSION="1.1.0"
    URL="${REPO_URL}/archive/v${MODULE_VERSION}.tar.gz"
    install -d -m 0755 "${TARGET_DIR}"
    wget -q -O - "$URL" | tar xfz - -C "${TARGET_DIR}" --strip-components 1

Proceed to enabling the module.

### Installation from GIT repository

Another convenient method is the installation directly from our GIT repository.
Just clone the repository to one of your Icinga Web 2 module path graphiteies.
It will be immediately ready for use:


    ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
    REPO_URL="https://github.com/icinga/icingaweb2-module-graphite"
    TARGET_DIR="${ICINGAWEB_MODULEPATH}/graphite"
    MODULE_VERSION="1.1.0"
    git clone "${REPO_URL}" "${TARGET_DIR}" --branch v${MODULE_VERSION}

You can now directly use our current GIT master or check out a specific version.

    cd "${TARGET_DIR}" && git checkout "v${MODULE_VERSION}"

Proceed to enabling the module.

### Enable the newly installed module

Enable the `graphite` module either on the CLI by running

    icingacli module enable graphite

Or go to your Icinga Web 2 frontend, choose `Configuration / Modules`,
select the `graphite` module and choose `State: enable`.
