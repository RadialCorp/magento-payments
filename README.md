# magento-payments-creditcard

[![Build Status](https://circleci.com/gh/RadialCorp/magento-payments-creditcard.svg?style=shield&circle-token=a6fa0ca1119eea7205d98e501ec08b0da59c892c)](https://circleci.com/gh/RadialCorp/magento-payments-creditcard)

## Installation

> The Radial Payments, Tax, and Fraud (PTF) extension is installed with [Composer](https://getcomposer.org/) and deployed to your Magento project directory with [Magento Composer Installer](https://github.com/Cotya/magento-composer-installer).

> To install the last stable release it is best to create a composer.json file containing the following: 

<pre>
{ 
   "name": "radial - magento-payment-tax-fraud", 
   "require": { 
     "radial/magento-payments-creditcard" : "^1.0" 
   }, 
   "extra": { 
     "magento-root-dir": ".", 
     "magento-deploystrategy": "copy", 
     "magento-force": true 
   } 
} 
</pre>

> Then run composer installer while in the directory that is your base Magento installation - please note that it is strongly recommended that this is done in a non-production environment for thorough testing before production deployment and production deployment should be done with a tagged set of files - the output should look something like this:

<pre>
[user@sandbox02 magento]$ /...path_to_composer.../composer.phar install
Loading composer repositories with package information
Updating dependencies (including require-dev)
  - Installing psr/log (1.0.0)
    Loading from cache

  - Installing symfony/polyfill-mbstring (v1.1.1)
    Loading from cache

  - Installing symfony/console (v2.8.5)
    Loading from cache

... more output ...

magento-hackathon/magento-composer-installer suggests installing colinmollenhour/modman (*)
magento-hackathon/magento-composer-installer suggests installing theseer/autoload (~1.14)
Package videlalvaro/php-amqplib is abandoned, you should avoid using it. Use php-amqplib/php-amqplib instead.
Writing lock file
Generating autoload files
./app/Mage.php was already patched
[user@sandbox02 magento]$ 
</pre>

> You will be prompted to define your Magento root, you should provide the root of your Magento installation.

> Why composer-based installation?  The Radial PTF extension leverages a number of open-source extensions to accomplish its goal, you can see them listed out in the composer output or later in your composer.lock file.  Composer is the easiest way to ensure that an installation of Magento contains all these dependencies for Radial PTF to function correctly.  

## Configuration

> The Radial PTF extension does not function unless it is a) configured and b) linked to an active account for accessing Radial's Public API's.  

> Step 1 - Rename the app/etc/rom.xml.sample file to rom.xml - make sure you clear any caches running on the store

> Step 2 - Log into Admin > System > Configuration > Radial - Payments, Tax, Fraud

> Step 3 - Your Radial Technical contact will provide you with the information needed to complete these fields

> <img src="magento-admin-ptf.png">

> Step 4 - Start using Radial Credit Card Processing as an active Payment Method in Magento - in Admin > System > Configuration > Payment Methods select the eBay Enterprise Credit Card header and set Enabled to Yes

> <img src="payment_method.png">

> Please remember to cache-clear after setting up data in Magento admin...

## Sign Up for Radial API Access

information coming soon...