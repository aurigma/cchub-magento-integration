# Aurigma Customers Canvas for Magento 2

Enable online print product personalization for your Magento 2 storefront.

## Compatibility

This release line supports Magento Open Source and Adobe Commerce 2.4.6 and all 2.4.6 patch versions (2.4.6*).

Composer constraint used by this package:

```
magento/product-community-edition >=2.4.6 <2.4.7
```

## Installation

Install the module via Composer and run standard Magento setup commands:

```bash
composer require aurigma/magento-customers-canvas
php bin/magento module:enable Aurigma_CustomersCanvas
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## Release Branches and Tags

Recommended versioning strategy:

- 1.x line: Magento 2.4.2-2.4.3
- 2.x line: Magento 2.4.4+
- 3.x line: Magento 2.4.6*

For each compatibility line, maintain a separate long-lived branch according to your team workflow.

Tag releases with SemVer tags such as `v3.0.0`, `v3.0.1`, `v3.1.0`.

If you need to publish only patch fixes for Magento 2.4.6* compatibility, continue with patch tags (`v3.0.1`, `v3.0.2`, ...).