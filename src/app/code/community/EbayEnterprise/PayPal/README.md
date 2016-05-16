![ebay logo](../../../../../../docs/static/logo-vert.png)

**Magento Retail Order Management Extension**
# PayPal Payments

The intended audience for this guide is Magento merchants, business users and system integrators.

## Contents

- [Introduction](#introduction)
- [Dependencies](#dependencies)
- [Configuration](#configuration)

## Introduction

The Magento Retail Order Management Extension enables PayPal payment processing via the Retail Order Management payment service.

## Configuration

The PayPal button image URLs may be optionally modified in configuration via local XML. The extension includes a sample configuration file—[`/path/to/magento/root/dir/app/etc/rom.xml.sample`](../../../../etc/rom.xml.sample)—that includes detailed documentation and example configuration options.

## Dependencies

### Magento Modules

- Mage_Core
- Mage_Checkout
- Mage_Sales
- Mage_Tax
- EbayEnterprise_Eb2cCore
- EbayEnterprise_MageLog

### Other Dependencies

- [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.
