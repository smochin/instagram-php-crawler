
# PHP Instagram Crawler
[![Join the chat at https://gitter.im/smochin/instagram-php-crawler](https://badges.gitter.im/smochin/instagram-php-crawler.svg)](https://gitter.im/smochin/instagram-php-crawler?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Total Downloads](https://img.shields.io/packagist/dt/smochin/instagram-php-crawler.svg?style=flat-square)](https://packagist.org/packages/smochin/instagram-php-crawler)
[![Latest Stable Version](https://img.shields.io/packagist/v/smochin/instagram-php-crawler.svg?style=flat-square)](https://packagist.org/packages/smochin/instagram-php-crawler)
![Branch master](https://img.shields.io/badge/branch-master-brightgreen.svg?style=flat-square)
[![Build Status](https://img.shields.io/travis/smochin/instagram-php-crawler/master.svg?style=flat-square)](http://travis-ci.org/#!/smochin/instagram-php-crawler)

A simple PHP Crawler for [Instagram](https://instagram.com).

## Installation
Package is available on [Packagist](http://packagist.org/packages/smochin/instagram-client),
you can install it using [Composer](http://getcomposer.org).

```shell
composer require smochin/instagram-php-crawler
```

### Dependencies
- PHP 7
- json extension
- cURL extension

## Get started

### Initialize the Crawler
```php
$crawler = new Smochin\Instagram\Crawler();
```

### Get a list of recently tagged media
```php
$media = $crawler->getMediaByTag('php');
```

### Get a list of recent media from a given location
```php
$media = $crawler->getMediaByLocation(225963881);
```

### Get the most recent media published by a user
```php
$media = $crawler->getMediaByUser('instagram');
```

### Get information about a media
```php
$media = $crawler->getMedia('0sR6OhmwCQ');
```

### Get information about a user
```php
$user = $crawler->getUser('jamersonweb');
```

### Get information about a location
```php
$location = $crawler->getLocation(225963881);
```

### Get information about a tag
```php
$tag = $crawler->getTag('php');
```

### Search for hashtags, locations and users
```php
$result = $crawler->search('recife');
```
