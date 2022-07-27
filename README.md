# Pricemotion for Magento 2

This extension integrates your Magento 2 store with
[Pricemotion](https://pricemotion.nl).

- [This extension on Magento
  Marketplace](https://marketplace.magento.com/pricemotion-module-pricemotion.html)
- [Information about this extension on Pricemotion.nl
  (Dutch)](https://www.pricemotion.nl/mogelijkheden/magento-2-extensie/)
- [Installation & user
  guide](https://docs.google.com/document/d/e/2PACX-1vS8n18Itwb_BeQw_7UvWTTOcpl_iXJLARzak6MPqX7JlNjGOoaS-WH9Cbyhroy3f9YInV3GbtvtsVSS/pub)

We recommend installing the extension via GitHub, because Magento Marketplace
releases are frequently delayed by Magento's review.

## Direct installation from GitHub

In order to install the development version, or a new version that has not yet
been published on Magento Marketplace, you may use Composer to directly install
the extension from GitHub.

Edit your `composer.json` and add the GitHub repository to the top of the
`repositories` section:

```
{
    "type": "vcs",
    "url": "https://github.com/pricemotion/magento2"
},
```

After you've done this, install or upgrade the extension using the command line:

```
# If you have not yet installed the extension
composer require pricemotion/module-pricemotion
```

```
# If you have installed the extension previously
composer update pricemotion/module-pricemotion
```

Lastly run the Magento update process:

```
bin/magento setup:upgrade
```
