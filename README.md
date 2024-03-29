Svea payment module for PrestaShop
==================================

Copyright (C) 2023 Svea Payments Oy

This library is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either version 2.1 of the License, or (at your option) any later
version. [GNU LGPL v. 2.1 @gnu.org] (https://www.gnu.org/licenses/lgpl-2.1.html)

This library is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License for more
details.

> Contact information:
Svea Payments Oy
Mechelininkatu 1a
00180 Helsinki
Finland
e-mail: info@svea.fi

Svea Payments Oy, hereby disclaims all copyright interest in the library 'Svea payment module' written for
Svea Payments Oy


Development
===========

This module follow PrestaShop coding standards. Please run

```
composer install --dev
composer dump-autoload --optimize --no-dev --classmap-authoritative
composer run autoindex
composer run header-stamp
composer run lint
composer run phpstan
```

Before committing your changes
