[![Test Coverage](https://api.codeclimate.com/v1/badges/63a3119e2bbbd693b63a/test_coverage)](https://codeclimate.com/github/averias/phpredis-bloom/test_coverage)
[![Maintainability](https://api.codeclimate.com/v1/badges/63a3119e2bbbd693b63a/maintainability)](https://codeclimate.com/github/averias/phpredis-bloom/maintainability)
[![Build Status](https://travis-ci.org/averias/phpredis-bloom.svg?branch=master)](https://travis-ci.org/averias/phpredis-bloom)
[![Packagist Version](https://img.shields.io/packagist/v/averias/phpredis-bloom.svg)](https://packagist.org/packages/averias/phpredis-bloom)
[![GitHub](https://img.shields.io/github/license/averias/phpredis-bloom.svg)](https://github.com/averias/phpredis-bloom)

<img src="docs/PhpRedisBloomLogo250x300.png" alt="PhpRedisBloom" align="right" />

# PhpRedisBloom
PHP client for [RedisLab/RedisBloom Module](https://oss.redislabs.com/redisbloom/), an extension of Redis core for 
probabilistic data structures.

## Table of content

- [Intro](#intro)
- [Requirements](#requirements)
- [Usage](#usage)
    - [Clients](#clients)
    - [Items](#items)
    - [Why having a RedisBloomClient and classes for each RedisBloom data types?](#why-having-a-redisbloomclient-and-classes-for-each-redisbloom-data-types)
    - [Automatic connection, disconnection and reconnection](#automatic-connection-disconnection-and-reconnection)
    - [Code Sample](#code-sample)
- [Commands](#commands)
    - [PhpRedisBloom commands](#phpredisbloom-commands)
    - [Phpredis commands](#phpredis-commands)
    - [Raw commands](#raw-commands)
- [Tests](#tests)
    - [On a local Redis server 4.0+ with RedisBloom module and Redis extension 5 installed](#on-a-local-redis-server-40-with-redisbloom-module-and-redis-extension-5-installed)
    - [Docker](#docker)
- [Examples](#examples)
- [License](#license)

## Intro
PhpRedisBloom provides the full set of commands for `RedisBloom Module` and it's built on top of [phpredis](https://github.com/phpredis/phpredis) 
and use it as Redis client, so you can also take advantage of some of the features included in `phpredis` as Redis client.

## Requirements
- Redis server 4.0+ version (Redis Modules are only available from Redis 4.0+)
- RedisBloom Module installed on Redis server as specified in [Building and running](https://oss.redislabs.com/redisbloom/Quick_Start/#building-and-running)
- PHP 7.2+ with PHP Redis extension 5 installed

## Usage

### Clients
There are 2 ways to execute PhpRedisBloom commands:

**Executing commands by using RedisBloomClient**

```php
use Averias\RedisBloom\Factory\RedisBloomFactory;

// instantiate a RedisBloomClient from RedisBloomFactory with default connection options
$factory = new RedisBloomFactory();
$client = $factory->createClient();

// then you can execute whatever redis bloom command for each of the 4 data types
$result = $client->bloomFilterAdd('filter-key', 'item-15');

```

**Executing commands by using RedisBloom data types classes (Bloom Filter, Cuckoo Filter, Count-Min Sketch and Top-K)**

```php
// example for BloomFilter data types class
use Averias\RedisBloom\Factory\RedisBloomFactory;

// instantiate a BloomFilter class from RedisBloomFactory with default connection options
$factory = new RedisBloomFactory();
$bloomFilter = $factory->createBloomFilter('filter-key');

// then you can execute whatever Bloom Filter command on 'filter-key' filter
// adding one item to Bloom Filter 'filter-key'
$result = $bloomFilter->add('item1'); // returns true

// adding 2 items more to Bloom Filter 'filter-key'
$result = $bloomFilter->multiAdd('item2', 15); // returns and array [true, true]

// checking if item 'item1' exists in 'filter-key' Bloom Filter
$result = $bloomFilter->exists('item1'); // returns true

// adding one item more
$result = $bloomFilter->add(17.2); // returns true

// checking if a list items exist in 'filter-key' Bloom Filter
$result = $bloomFilter->multiExists('item1', 15, 'foo'); // returns and array [true, true, false] since 'foo' doesn't exists 
```


### Items
The only allowed item values to add or insert in the 4 data structure are string, integers, and float, but notice 
that all items are stored as strings, so adding the integer 13 or the string `13` will end in the same result.
For those data structures that allow inserting repeated items (like Cuckoo Filter) it will increase the count of that 
item or will fail in the second insertion in case of structures that do not allow inserting repeated items.

Same behavior for floats, inserting first a float like 17.2 and then insert its representation as string `17.2` will 
throw an exception in the second insertion in Bloom Filters which doesn't allow repeated items and in case of other 
structures like Cuckoo Filter, Count-Min Sketch and Top-k will increase the counter to 2 after the second insertion.

You can take a look to [examples/inserting-numbers.php](https://github.com/averias/phpredis-bloom/blob/master/examples/inserting-numbers.php) 
to see an example of this behavior.


### Why having a RedisBloomClient and classes for each RedisBloom data types?

- RedisBloomClient allows you execute whatever RedisBloom command (Bloom Filter, Cuckoo Filter, Mins-Sketch and Top-K 
commands) over different filters and also to execute Redis commands and raw Redis commands. So it is a client for general 
purposes and it is recommended when you need to manage different filters and keys or even when you want to execute 
normal Redis commands
- RedisBloom data types classes (Bloom Filter, Cuckoo Filter, Mins-Sketch and Top-K classes) just execute commands 
that belongs to that data type and over just one filter. They are useful when you need to manage just one filter. 


### Automatic connection, disconnection and reconnection

RedisBloomClient and Redis Bloom data types automatically connect Redis after creation, you can disconnect them from the 
Redis instance by calling its `disconnect` method:

`$client->disconnect()`

or 

`$bloomFilter->disconnect()`

which will return true or false depending on the disconnection was possible.

After one successful disconnection the client or data type object will reconnect automatically if you reuse the object 
for sending more commands (see example below) 


### Code Sample

The following code snippet show how to instantiate RedisBloom clients and BloomFilter data type with different 
connection configurations
 
```php
<?php

use Averias\RedisBloom\Enum\Connection;
use Averias\RedisBloom\Factory\RedisBloomFactory;

require(dirname(__DIR__).'/vendor/autoload.php');

const EXAMPLE_FILTER = 'example-filter';

/**
 * Default connection params:
 * [
 *     'host' => '127.0.0.1',
 *     'port' => 6379,
 *     'timeout' => 0.0, // seconds
 *     'retryInterval' => 15 // milliseconds
 *     'readTimeout' => 2, // seconds
 *     'persistenceId' => null // string for persistent connections, null for no persistent ones
 *     'database' => 0 // Redis database index [0..15]
 * ]
 *
 * you can create a factory with default connection by not passing any param in the constructor
 * $defaultFactory = new RedisBloomFactory();
 */

// create a factory with default connection but pointing to database 15
$factoryDB15 = new RedisBloomFactory([Connection::DATABASE => 15]);

// it creates a RedisBloomClient with same default connection configuration as specified in factory above
$clientDB15 = $factoryDB15->createClient();

// using the same factory you can create a BloomFilter object pointing 
// to database 14 and filter name = 'example-filter'
$bloomFilterDB14 = $factoryDB15->createBloomFilter(EXAMPLE_FILTER, [Connection::DATABASE => 14]);

// add 'item-15' to 'example-filter' bloom filter on database 15
$clientDB15->bloomFilterAdd(EXAMPLE_FILTER, 'item-15');

// add 'item-14' to 'example-filter' bloom filter on database 14
$bloomFilterDB14->add('item-14');

// disconnect
$bloomFilterDB14->disconnect();

// create another RedisBloomClient pointing to database 14
$clientDB14 = $factoryDB15->createClient([Connection::DATABASE => 14]);

$result = $clientDB15->bloomFilterExists(EXAMPLE_FILTER, 'database15'); //true
$result = $clientDB15->bloomFilterExists(EXAMPLE_FILTER, 'database14'); // false

$result = $clientDB14->bloomFilterExists(EXAMPLE_FILTER, 'database15'); // false
$result = $clientDB14->bloomFilterExists(EXAMPLE_FILTER, 'database14'); // true

// delete bloom filter on database 15
$clientDB15->executeRawCommand('DEL', EXAMPLE_FILTER);

// delete bloom filter on database 14
$clientDB14->del(EXAMPLE_FILTER);

// disconnect

$clientDB15->disconnect();
$clientDB14->disconnect();

// automatic reconnection
$bloomFilterDB14->add('reconnected');
$exist = $bloomFilterDB14->exists('reconnected'); //true
$bloomFilterDB14->disconnect();

```

## Commands
#### PhpRedisBloom commands

Phpredis-bloom provides all the commands for the four RedisBloom data types, please follow the links below for a 
detailed info for each one:

- [Bloom Filter](https://github.com/averias/phpredis-bloom/blob/master/docs/BLOOM-FILTER-COMMANDS.md)
- [Cuckoo Filter](https://github.com/averias/phpredis-bloom/blob/master/docs/CUCKOO-FILTER-COMMANDS.md)
- [Count-Min-Sketch](https://github.com/averias/phpredis-bloom/blob/master/docs/COUNT-MIN-SKETCH-COMMANDS.md)
- [Top-K](https://github.com/averias/phpredis-bloom/blob/master/docs/TOP-K-COMMANDS.md)

#### Phpredis commands

You can send Redis commands as specified in [phpredis documentation](https://github.com/phpredis/phpredis#table-of-contents)

#### Raw commands
You can send whatever you want to Redis by using `RedisBloomClient::executeRawCommand`:

```php
use Averias\RedisBloom\Enum\BloomCommands;

// raw Redis Bloom command
$client->executeRawCommand(BloomCommands::BF_ADD, 'filter-name', 'value');

// raw Redis command
$client->executeRawCommand('hget', 'hash-key', 'foo');
``` 

## Tests
### On a local Redis server 4.0+ with RedisBloom module and Redis extension 5 installed
From console run the following command from the root directory of this project:

`./vendor/bin/phpunit`

if you don't have configured your local Redis server in 127.0.0.1:6379 you can set REDIS_TEST_SERVER, REDIS_TEST_PORT 
and REDIS_TEST_DATABASE in `./phpunit.xml` file with your local Redis host, port and database before running the above 
command.
  
### Docker
Having Docker installed, run the following command in the root directory of this project:

`bash run-tests-docker.sh`

by running the above bash script, two docker services will be built, one with PHP 7.2 with xdebug and redis extensions
enabled and another with the image of `redislab\rebloom:2.0.3` (Redis server 5 with RedisBloom module installed). 
Then the tests will run inside `phpredis-bloom` docker service container and finally both container will be stopped.

## Examples
- [Factory](https://github.com/averias/phpredis-bloom/blob/master/examples/factory.php)
- [BF copy](https://github.com/averias/phpredis-bloom/blob/master/examples/bloom-filter-copy.php)
- [CMS increment](https://github.com/averias/phpredis-bloom/blob/master/examples/count-min-sketch-increment-by.php)
- [CMS Merge](https://github.com/averias/phpredis-bloom/blob/master/examples/count-min-sketch-merge.php)

## License
PhpRedisBloom code is distributed under MIT license, see [LICENSE](https://github.com/averias/phpredis-bloom/blob/master/LICENSE) 
file
