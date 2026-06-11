# Change Log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [3.0.0] - 18.12.2025
Major overhaul and M2M callback support!

### Added
- Log for payment attempts
  - New view to see the payment attempts
  - More informatiton on order page for the payment attempts for that order
- Swedish translations

### Changed
- Multiple Payment Attempts Support
  - **Reworked payment ID system** to support multiple payment attempts per cart
  - Added `id_mt_payment` as auto-increment primary key (replacing `id_order`)
  - Added `id_cart`, `attempt`, and `pmt_id` columns to payment table
  - Added `logs` column for detailed payment attempt logging
  - Payment ID formula now includes attempt number: `(cart_id + 100) × 10000 + attempt`
  - Each cart can now have multiple payment attempts with unique pmt_id values
- Machine-to-Machine (M2M) Callback Handling
  - rewritten validation.php to properly handle M2M callbacks
  - Proper handling of race conditions between M2M callback and browser return (there is still a really small race condition possibility but it should be minor)
- Redirect to payment page
  - Separate "to payment" page which initiates the payment
  - no external resources and extra hardening on that page
- If settings are not set the payment method wont be shown in storefront
- Show indication of using Sandbox mode in storefront
- Better error view with "try again" link
- Checked fi and en translations
- Removed encoding setting (everybody is utf-8 now)
- Removed some old templates and code
- Updated github workflow to use new actions
- Updated composer dependencies
- Updated LGPLGHeader to 2026

### Fixes
- CURLOPT_SSL_VERIFYPEER true
- Fixed some comments and phpstan
- More type hints and PHPDocs
- Fix more characters that are not allowed in Svea (try to transliterate)

## [2.3.0] - 13.11.2023
- PrestaShop 8.1 support
- Drop support for PrestaShop below 1.7.6
- Minimum php version 7.2

### Changed
- added LICENSE file
- use `displayHeader` instead of deprecated `Header` hook
- use `displayPaymentReturn` instead of deprecated `paymentReturn` hook
- use `displayPDFInvoice` instead of deprecated `PDFInvoice` hook
- use json_encode instead of deprecated and removed Tools::jsonEncode

### Added
- README file with not much information yet

## [2.2.4] - 10.02.2022
- Updated logos

## [2.2.3] - 02.12.2021
### Changed
- Replacing translations for re-branding from Maksuturva to Svea

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
