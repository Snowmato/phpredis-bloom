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

namespace Averias\RedisBloom\Tests\Integration\Command\CountMinSketch;

use Averias\RedisBloom\Enum\Keys;
use Averias\RedisBloom\Exception\ResponseException;
use Averias\RedisBloom\Tests\Integration\BaseTestIntegration;

class CountMinSketchIncrementByCommandTest extends BaseTestIntegration
{
    public function testIncrementSuccessfully(): void
    {
        static::$reBloomClient->countMinSketchInitByDim(Keys::INCREMENT_BY_1, 100, 4);
        $result = static::$reBloomClient->countMinSketchIncrementBy(
            Keys::INCREMENT_BY_1,
            'green',
            40,
            'black',
            90,
            'orange',
            6
        );

        $this->assertEquals(40, $result[0]);
        $this->assertEquals(90, $result[1]);
        $this->assertEquals(6, $result[2]);

        $result = static::$reBloomClient->countMinSketchIncrementBy(Keys::INCREMENT_BY_1, 12, 31, 13.4, 32);

        $this->assertEquals(31, $result[0]);
        $this->assertEquals(32, $result[1]);
    }

    /**
     * @dataProvider getExceptionDataProvider
     * @param string $key
     * @param array $arguments
     */
    public function testIncrementException($key, $arguments): void
    {
        $this->expectException(ResponseException::class);
        static::$reBloomClient->countMinSketchInitByDim(Keys::INCREMENT_BY_2, 4, 4);
        static::$reBloomClient->countMinSketchIncrementBy($key, ...$arguments);
    }

    public function getExceptionDataProvider(): array
    {
        return [
            [Keys::INCREMENT_BY_2, [true, 44]],
            [Keys::INCREMENT_BY_2, ['bar', true]],
            [Keys::INCREMENT_BY_2, ['foo', 4, 'bar']],
            [Keys::INCREMENT_BY_2, ['foo', 4.3]],
            [Keys::INCREMENT_BY_2, ['baz', true]],
            [Keys::INCREMENT_BY_2, ['baz', 10]] // non-existent key
        ];
    }
}
