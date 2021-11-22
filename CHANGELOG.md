# Changelog

## 1.3.1 - 2021-11-22

* Only trigger indexers when other fields than the last updated timestamp are
  changed.

## 1.3.0 - 2021-06-25

* Add option to round price up or down according to selected precision.

## 1.2.0 - 2021-04-19

* Add push API allowing Pricemotion to update products as soon as new data is
  available, and without waiting for the cron job.

## 1.1.15 - 2021-04-14

* Log uncaught exceptions and errors during cron run to `pricemotion.log`.
* Fix Magento setup when module is installed during first Magento installation.

## 1.1.14 - 2021-04-14

* Fix update command breaking when price rule is disabled.

## 1.1.13 - 2020-12-16

* Trigger price, attribute indexers even if indexes run on schedule.

## 1.1.12 - 2020-12-09

* Reindex product after update. This should prevent stale prices from being
  displayed on the storefront.

## 1.1.11 - 2020-12-07

* Fix PriceRules component constructor. This restores the mass price rule update
  functionality and unbreaks DI compilation.

## 1.1.10 - 2020-12-04

* Use user locale for Pricemotion widgets in admin interface. (Dutch and English
  languages are supported.)

## 1.1.9 - 2020-12-04

* Mark products as updated even if API errors occur.  This is to prevent the
  same products from being updated every minute, even when the results don't
  change.
* End cron job when running out of time.

## 1.1.8 - 2020-12-04

* Emulate default store view in cronjob.
* Log EAN, price, list price attribute settings during `pricemotion:update`
  command.
* Log manual update runs so they can be distinguished from cron runs in the
  logs.
* Log "no products need updating" so that cron job execution can be verified.

## 1.1.7 - 2020-12-03

* Add `--ean` option to `pricemotion:update` command allowing specifying
  specific products to update.
* Log all product attributes to the console when using `pricemotion:update`.
* Prevent "Got 0 products for update" message from being logged.

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
