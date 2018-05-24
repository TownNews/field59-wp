Binaries should be stored in this location. For purposes of version control, binaries should be
stored in the package with their extension so that source control editors will format and lint
check them properly.

The scripts created here should be the base name without the package encoded into the script name.
Hence, a script that will ultimately be called _example-cli_ would be stored in this directory as:

```cli.php

The RPM should install this file into:

```/usr/local/bin/%{name}-cli

It should additionally prepend to this script:

```#!/bin/env php

The townnews-package RPM provides some utilties to make this easier and should be reviewed for more
details.
