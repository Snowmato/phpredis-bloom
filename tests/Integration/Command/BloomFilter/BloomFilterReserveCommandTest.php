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

namespace Averias\RedisBloom\Tests\Integration\Command\BloomFilter;

use Averias\RedisBloom\Enum\OptionalParams;
use Averias\RedisBloom\Exception\ResponseException;
use Averias\RedisBloom\Tests\Integration\BaseTestIntegration;

class BloomFilterReserveCommandTest extends BaseTestIntegration
{
    /**
     * @dataProvider getDataProvider
     * @param string $key
     * @param float $errorRate
     * @param int $capacity
     * @param array $options
     */
    public function testSuccessReservation(string $key, float $errorRate, int $capacity, array $options): void
    {
        $result = static::$reBloomClient->bloomFilterReserve($key, $errorRate, $capacity, $options);
        $this->assertTrue($result);
    }

    /**
     * @dataProvider getExceptionDataProvider
     * @param string $key
     * @param float $errorRate
     * @param int $capacity
     * @param array $options
     */
    public function testReservationException(string $key, float $errorRate, int $capacity, array $options): void
    {
        $this->expectException(ResponseException::class);
        static::$reBloomClient->bloomFilterReserve($key, $errorRate, $capacity, $options);
    }

    public function getDataProvider(): array
    {
        return [
            ['key-reserve1', 0.1, 10000, []],
            ['key-reserve2', 0.01, 1000000, []],
            ['key-reserve3', 0.001, 10000000, [OptionalParams::NON_SCALING => true]],
            // even when NON_SCALING is a wrong value, it must be bool, it is ignored
            ['key-reserve4', 0.0001, 100000000, [OptionalParams::NON_SCALING => 12]],
            ['key-reserve5', 0.00001, 1000000000, [OptionalParams::EXPANSION => 41]]
        ];
    }

    public function getExceptionDataProvider(): array
    {
        return [
            ['key-reserve1', 0.1, 10000, []], // key already exists
            ['key-reserve6', 0.0, 10000, []], // error must be > 0.0
            ['key-reserve6', 0.1, 10000, [OptionalParams::EXPANSION => 'foo']], // expansion must be integer
            ['key-reserve6', 0.1, 10000, [OptionalParams::EXPANSION => 12.3]], // expansion must be integer
            // a combination of EXPANSION and NON_SCALING is not allowed
            ['key-reserve6', 0.001, 10000000, [OptionalParams::EXPANSION => 4, OptionalParams::NON_SCALING => true]],
            ['key-reserve6', 1.0, 10000, []] // error must be < 1.0
        ];
    }
}
