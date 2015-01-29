FinZip
======

Fetch and parse Finnish streetnames, zip codes and municipalities provided by Posti.fi. Also includes geocoder for geotagging addresses.

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
require 'vendor/autoload.php';
$finzip = new FinZip();

//Load list of localities
print_r($finzip->getLocalities());

//Load all Finnish street addresses and their zip codes
print_r($finzip->getStreetnames());

```

License
-------
LGPL-3.0