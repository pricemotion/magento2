# Changelog

## 1.1.6 - 2020-11-20

* Fix cron time limit.
* Disable time limit when running via `pricemotion:update`.
* Avoid trying to apply price rules if settings are empty.

## 1.1.5 - 2020-10-26

* Explicitly save only attributes changed during price update.

## 1.1.4 - 2020-09-02

* Fix version detection when installed directly from GitHub using Composer.

## 1.1.3 - 2020-09-02

* Fix template load issues when installed via Composer.

## 1.1.2 - 2020-08-18

* Mark PHP 7.4 as supported.
* Fix type hint on product observer that might cause issues when attributes are
  not configured.

## 1.1.1 - 2020-08-14

* Interpret price difference filter values like they are displayed.
* Open Pricemotion tab on product edit page if input is invalid.

## 1.1.0 - 2020-08-11

* Fix Pricemotion attribute and price updates.  This did not work in the initial release.
* Write logs to a separate logfile (`pricemotion.log`).
* Fix exception during updates when price rules were not configured.
* Reset Pricemotion attributes, update time when relevant attributes are
  changed.
* Add option to set maximum discount on list price.
* Add mass price rule edit functionality.

## 1.0.0 - 2020-07-16

* Initial public release.
