Maksuturva/eMaksut payment module for Prestashop ecommerce
==========================================================
Copyright (C) 2012 Suomen Maksuturva Oy

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.
[GNU LGPL v. 2.1 @gnu.org] (https://www.gnu.org/licenses/lgpl-2.1.html)

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

> Contact information:  
Suomen Maksuturva Oy  
Ruoholahdenkatu 23  
00180 Helsinki  
Finland  
e-mail: info@maksuturva.fi
 
Suomen Maksuturva Oy, hereby disclaims all copyright interest in
the library 'Maksuturva/eMaksut payment module' written
for Suomen Maksuturva Oy

8 March, 2012 Konsta Karvinen, 
ICT Development Manager / Suomen Maksuturva Oy

CHANGELOG
---------

###rev. 122 23.10.2014
* New: 
	- Support for additional payment fees in response message. 
	- Product article number referes now to product reference number. If reference number is not available, then to EAN13-number if available.
	- A default Finnish translation package added for the payment module. 

* Bugfixes:
	- Fixed translation variables and messages. Note: this change will overwrite ALL previous module translations. See section 5.1 Upgrading an existing module for instructions.
	- Payment module sends user locale to Maksuturva (the customer sees Maksuturva payment page in that language by default)

* Documentation updated

###rev. 121 6.8.2014
* Bugfixes:
	- the module now supports Prestashop up to version 1.6.
	- the module now removes quotation marks from product names and description – previously it caused errors in hash calculation
	- product SKU is added when available, shipping method name used as shipping name when available
	- Optional parameters (e.g. pre-selected payment method, buyer's identification code) are now included in the hash calculation when available (currently not available, though, but enabling is possible in further development - see _construct -method in MaksuturvaGatewayImplementation.php and Maksuturva Payment interface description).
	- Module directory structure updated to enable installation through Prestashop user interface. 

* Documentation updated.

