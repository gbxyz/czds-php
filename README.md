# CZDS Library for PHP

This repository contains a simple client library for accessing [ICANN](https://icann.org)'s [Centralized Zone Data Service (CZDS)](https://czds.icann.org).

## Installation

```
composer require gbxyz/czds
```

## Usage:

```php
require_once 'vendor/autoload.php';

$client = new gbxyz\czds\client;

$client->login($username, $password);

// get a list of available zone files as an array
$zones = $client->getZones();

// save the zone to disk
$client->saveZone($zones[0]);

// get a handle to a zone file
echo stream_get_contents($client->getZoneHandle($zones[1]));

// get zone contents
echo $client->getZoneContents($zones[2]);

// get an iterator which returns Net_DNS2_RR_* objects
foreach ($client->getZoneRRs($zones[3]) as $rr) {
    printf("Owner name: %s, TTL: %u, type: %s\n", $rr->name, $rr->ttl, $rr->type);
    echo (string)$rr;
}
```
