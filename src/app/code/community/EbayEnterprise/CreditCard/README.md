![ebay logo](../../../../../../docs/static/logo-vert.png)

**Magento Retail Order Management Extension**
# Credit Card Payments

The intended audience for this guide is Magento merchants, business users and system integrators.

## Contents

- [Introduction](#introduction)
- [Client-side Encryption](#client-side-encryption)
- [Local XML Configuration](#local-xml-configuration)
- [Dependencies](#dependencies)

## Introduction

The Magento Retail Order Management Extension enables credit card payment processing via the Retail Order Management payment service. The extension will authorize the credit card transaction on order submission. The Retail Order Management payment service will handle reauthorization and payment capture when appropriate. 

The extensions Credit Card payment method can be configured at **System > Configuration > Sales > Payment Methods > eBay Enterprise Credit Card**.

## Client-side Encryption

The extension provides the option to use client-side encryption to prevent credit card numbers and card security codes from being sent to Magento as raw text. Using client-side encryption option may allow the merchant to qualify for the PCI DSS Self-Assessment Questionnaire (SAQ) as an A-EP merchant.

- [Understanding the SAQs for PCI DSS v3.0](https://www.pcisecuritystandards.org/documents/Understanding_SAQs_PCI_DSS_v3.pdf) [PDF]
- [Self-Assessment Questionnaire A-EP and Attestation of Compliance](https://www.pcisecuritystandards.org/documents/SAQ_A-EP_v3.pdf) [PDF]

## Local XML Configuration

Credit card types in Magento must be mapped to supported tender types in the eBay Enterprise Retail Order Management payment service. This configuration is done via local XML configuration. The extension includes a sample configuration file—[`/path/to/magento/root/dir/app/etc/rom.xml.sample`](../../../../etc/rom.xml.sample)—that includes detailed documentation and example configuration options. Only card types that have a mapping to a payment service tender type will be available in Magento.

```xml
<ebayenterprise_creditcard>
	<!--
	Credit Card Tenders: Map Magento credit card types to ROM credit card tender types. Node names must match
	the credit card code as configured in Mage_Payment's etc/config.xml. Values must match credit card tender
	types supported by the ROM payment service.
	-->
	<tender_types>
		<AE>AM</AE>
		<DI>DC</DI>
		<MC>MC</MC>
		<VI>VC</VI>
	</tender_types>
</ebayenterprise_creditcard>
```

## Dependencies

### Magento Modules

- Mage_Core
- Mage_Checkout
- Mage_Payment
- EbayEnterprise_Eb2cCore
- EbayEnterprise_MageLog

### Other Dependencies

- [RetailOrderManagement-SDK](https://github.com/eBayEnterprise/RetailOrderManagement-SDK)

### Test Dependencies

- EcomDev_PHPUnit

- - -
Copyright © 2014 eBay Enterprise, Inc.
