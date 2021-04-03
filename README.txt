CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Using the module


INTRODUCTION
------------

Current Maintainers: cecrs, acrollet

The Site Alert module is a lightweight solution for allowing
site administrators to easily place an alert on their site,
for example for maintenance downtime, or any general
informational message. Alerts have start and end date/times,
and can be assigned a severity level. Messages are refreshed by
ajax and not subject to site caching, so changes made in the
ui will be automatically displayed to users without
necessitating a cache clear. The module provides site alert entities,
and a block that will display all active site alerts.


INSTALLATION
------------

Installation requires nothing more then enabling the site alert module.
Simple!


USING THE MODULE
----------------

- Enable the Site Alert module.
- Ensure that all necessary roles have the 'administer site alerts' permission.
  (All roles can view alerts).
- Add one or more site alert entities at `admin/config/system/alerts`.
- Ensure the site alerts you want to display in the site are 'active'. If an
  alert is inactive it is temporarily disabled. This can be handy if you want to
  reuse an alert again at a later moment without having to recreate it.
- Add the 'Site Alert' block to whichever region(s) you wish it to appear in.

Enjoy your exciting new site alert(s)!


DRUSH COMMANDS
--------------

Drush can be used to create, delete, enable and disable site alerts from the
command line. The Drush integration requires PHP 7.1 or higher.

To find out more, run the following commands:

- `drush site-alert:create --help`
- `drush site-alert:delete --help`
- `drush site-alert:disable --help`
- `drush site-alert:enable --help`
