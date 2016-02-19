# Change Log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

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

[Unreleased]: https://github.com/maksuturva/prestashop_payment_module/compare/2.0.0...HEAD
[2.0.0]: https://github.com/maksuturva/prestashop_payment_module/compare/122...2.0.0
[122]: https://github.com/maksuturva/prestashop_payment_module/compare/121...122
