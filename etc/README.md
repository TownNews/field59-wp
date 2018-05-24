This directory should house any and all configuration settings that
would by used throughout the application. Types of configurations
that should be stored here include:

* Crontabs
* Logsys INI files
* PHP INI files
* Apache/Nginx configuration files
* Apache Solr configurations
* Outage Control INI files
* Procmon INI files
* Report INI files
* Supervisor files

== Crontabs ==
Should be stored in:

cron.d/*.crt

For details on how to format this file, see:

man 5 crontab

Scripts will probably need to be wrapped using the CRON_SINGLE or
CRON_FAILOVER GPP macros. For information on that, see:

http://docs.sys1.vip.inn.mwn.leeent.net/display/DEV/2018/02/12/Cron+jobs+in+all+software+packages+will+need+to+be+updated

== Logsys INI files ==
Should be stored in:

logsys.d/*.ini

The INI files should conform to the specification about LogSys
configuration files. See:

http://docs.sys1.vip.inn.mwn.leeent.net/display/SS/Logsys+INI

== PHP INI files ==
INI files can be stored in:

php.d/*.ini

These files should be installed into:

/usr/local/etc/php.d/global.d
