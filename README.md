# CZDS Library for PHP

This repository contains a simple client library for accessing [ICANN](https://icann.org)'s [Centralized Zone Data Service (CZDS)](https://czds.icann.org).

You will need to create a user account on the CZDS, and request access to at least one TLD, for this to be useful!

## Installation

Add the library as a dependency to your project using `composer`:

```
composer require gbxyz/czds
```

## Usage

Load the library into your code using Composer's autoload function:

```php
require_once 'vendor/autoload.php';
```

## Create a client object

```php
$client = new gbxyz\czds\client;

$client->login($username, $password);
```

The CZDS API issues authentication tokens that are valid for 24 hours. These tokens are cached locally so this method will only incur am HTTP round trip once per day.

## Get a list of available zone files

This returns an array of TLDs:

```php
$zones = $client->getZones();
```

## Save a zone file to disk

```php
$client->saveZone($zone, '/tmp/zonefile.txt');
```

## Get a file descriptor for a zone file

```php
$fh = $client->getZoneHandle($zone);

echo stream_get_contents($fh);
```

## Get the contents of a zone file

```php
$zone = $client->getZoneContents($zone);

echo $zone;
```

## Get an iterator

This is useful for large zones. Instead of loading the entire zone into memory, you get an object can be iterated on, which returns objects which are instances of the various `Net_DNS2_RR_*` classes.

```php

$iterator = $client->getZoneRRs($zone);

foreach ($iterator as $rr) {
    printf("Owner name: %s, TTL: %u, type: %s\n", $rr->name, $rr->ttl, $rr->type);
    echo (string)$rr;
}
```
