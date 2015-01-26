FinZip
======

Fetch and parse Finnish zip codes (incl. localities &amp; street addresses) provided by Itella

Installation
------------
The recommended way to install Connection.php is through [Composer](http://getcomposer.org).
```json
{
	"require": {
		"tangervu/finzip": "dev-master",
		"php-google-maps/php-google-maps": "dev-master"
	}
}
```

Examples
--------
```php
<?php
require 'FinZip.php';
$finzip = new FinZip();

//Load list of localities
print_r($finzip->getLocalities());

//Load all Finnish street addresses and their zip codes
print_r($finzip->getStreetnames());

```

License
-------
LGPL-3.0