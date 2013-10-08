FinZip
======

Fetch and parse Finnish zip codes (incl. localities &amp; street addresses) provided by Itella

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