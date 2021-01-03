<?php
/**
 * @project   phpredis-bloom
 * @author    Rafael Campoy <rafa.campoy@gmail.com>
 * @copyright 2019 Rafael Campoy <rafa.campoy@gmail.com>
 * @license   MIT
 * @link      https://github.com/averias/phpredis-bloom
 *
 * Copyright and license information, is included in
 * the LICENSE file that is distributed with this source code.
 */

namespace Averias\RedisBloom\Tests\Integration\DataTypes;

use Averias\RedisBloom\DataTypes\BloomFilter;
use Averias\RedisBloom\Enum\Keys;
use Averias\RedisBloom\Enum\OptionalParams;
use Averias\RedisBloom\Exception\RedisClientException;
use Averias\RedisBloom\Exception\ResponseException;
use Averias\RedisBloom\Tests\Integration\BaseTestIntegration;

class BloomFilterTest extends BaseTestIntegration
{
    /** @var BloomFilter */
    protected static $bloomFilter;

    /**
     * @throws RedisClientException
     */
    public static function setUpBeforeClass(): void
    {
        static::$bloomFilter = static::$factory->createBloomFilter(Keys::BLOOM_FILTER, static::getReBloomClientConfig());
        static::$reBloomClient  = self::getReBloomClient();
    }

    public function testReserve(): void
    {
        $result = static::$bloomFilter->reserve(0.1, 50, [OptionalParams::EXPANSION => 4]);
        $this->assertTrue($result);
    }

    public function testAdd(): void
    {
        $result = static::$bloomFilter->add(1);
        $this->assertTrue($result);
        $exists = static::$bloomFilter->exists(1);
        $this->assertTrue($exists);
    }

    public function testMultiAdd(): void
    {
        $values = range(2, 5);
        $result = static::$bloomFilter->multiAdd(...$values);
        foreach ($result as $item) {
            $this->assertTrue($item);
        }

        $exists = static::$bloomFilter->multiExists(...$values);
        foreach ($exists as $item) {
            $this->assertTrue($item);
        }
    }

    public function testInsertWithOptions(): void
    {
        $values = range(6, 20);
        $result = static::$bloomFilter->insert($values, [OptionalParams::CAPACITY => 100, OptionalParams::ERROR => 0.01]);
        foreach ($result as $item) {
            $this->assertTrue($item);
        }

        $exists = static::$bloomFilter->multiExists(...$values);
        foreach ($exists as $item) {
            $this->assertTrue($item);
        }
    }

    public function testLoadChunk(): void
    {
        $newBloomFilter = static::$factory->createBloomFilter('bf-load-chunk', static::getReBloomClientConfig());

        list ($iterator, $data) = static::$bloomFilter->scanDump(0);
        $result = $newBloomFilter->loadChunk($iterator, $data);

        $this->assertTrue($result);
    }

    public function testCopy(): void
    {
        $result = static::$bloomFilter->copy('other-bloom-filter');
        $this->assertTrue($result);

        $otherBloomFilter = static::$factory->createBloomFilter('other-bloom-filter', static::getReBloomClientConfig());

        $values = range(1, 20);
        $exists = $otherBloomFilter->multiExists(...$values);
        foreach ($exists as $item) {
            $this->assertTrue($item);
        }
    }

    public function testCopyExceptionBecauseNoSourceFilter(): void
    {
        $this->expectException(ResponseException::class);
        $newBloomFilter = static::$factory->createBloomFilter('new-bloom-filter', static::getReBloomClientConfig());
        $newBloomFilter->copy('other-bloom-filter');
    }


    public function testInfo(): void
    {
        $result = static::$bloomFilter->info();
        $this->assertArrayHasKey(Keys::CAPACITY, $result);
        $this->assertArrayHasKey(Keys::SIZE, $result);
        $this->assertEquals(1, $result[Keys::NUMBER_FILTERS]);
        $this->assertEquals(20, $result[Keys::NUMBER_ITEMS_INSERTED]);
    }

    public function testDisconnection(): void
    {
        $clientsInfo = static::$reBloomClient->info('clients');
        $connectedClientsBefore = $clientsInfo['connected_clients'];

        $disconnected = static::$bloomFilter->disconnect();
        $this->assertTrue($disconnected);

        $clientsInfo = static::$reBloomClient->info('clients');
        $connectedClientsAfter = $clientsInfo['connected_clients'];

        $this->assertEquals(1, $connectedClientsBefore - $connectedClientsAfter);
    }
}
