# Change Log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [2.2.1] - 17.10.2019
### Changed
- Replacing images for re-branding from Maksuturva to Svea

## [2.2.0] - 24.6.2019
### Changed
- Support for PHP version 7.3/7.3
- Added support for handling payments with error or canceled payments (no order created in PrestaShop if order is canceled)

## [2.1.0] - 15.5.2017
### Changed
- Replace payment details into database instead of inserting them, to avoid issues when test orders have been purged
with e.g. PS Cleaner module before going live and the same IDs are used for new orders
### Added
- Support for PrestaShop version 1.7.x

## [2.0.0] - 19.2.2016
### Changed
- Refactor source code to comply with PrestaShop standards and best practices

### Added
- Support for payment prefixes which help to avoid collisions after re-installation

### Fixed
- Orders that include "backorder" products are processed correctly

## [122] - 23.10.2014
### Added
- Support for additional payment fees in response message.
- Product article number refers now to product reference number. If reference number is not available, then to EAN13
number if available.
- A default Finnish translation package added for the payment module.

### Fixed
- Fixed translation variables and messages. Note: this change will overwrite ALL previous module translations. See
section 5.1 Upgrading an existing module for instructions.
- Payment module sends user locale to Maksuturva (the customer sees Maksuturva payment page in that language by default)

## 121 - 6.8.2014
### Fixed
- The module now supports PrestaShop up to version 1.6.
- The module now removes quotation marks from product names and description – previously it caused errors in hash calc
- Product SKU is added when available, shipping method name used as shipping name when available
- Optional parameters (e.g. pre-selected payment method, buyer's identification code) are now included in the hash calc
when available (currently not available, though, but enabling is possible in further development - see "_construct"
method in MaksuturvaGatewayImplementation.php and Maksuturva Payment interface description).
- Module directory structure updated to enable installation through PrestaShop user interface.

[Unreleased]: https://github.com/maksuturva/prestashop_payment_module/compare/2.1.0...HEAD
[2.2.0]: https://github.com/maksuturva/prestashop_payment_module/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/maksuturva/prestashop_payment_module/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/maksuturva/prestashop_payment_module/compare/122...2.0.0
[122]: https://github.com/maksuturva/prestashop_payment_module/compare/121...122
