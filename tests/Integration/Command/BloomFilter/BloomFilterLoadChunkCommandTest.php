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

use Averias\RedisBloom\Enum\Keys;
use Averias\RedisBloom\Exception\ResponseException;
use Averias\RedisBloom\Tests\Integration\BaseTestIntegration;

class BloomFilterLoadChunkCommandTest extends BaseTestIntegration
{
    protected static $data = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    public static function setUpBeforeClass():void
    {
        parent::setUpBeforeClass();
        static::$reBloomClient->bloomFilterMultiAdd(Keys::DEFAULT_KEY, ...self::$data);
    }

    public function testLoadChunk(): void
    {
        $iterator = 0;
        while (true) {
            list ($iterator, $data) = $result = static::$reBloomClient->bloomFilterScanDump(
                Keys::DEFAULT_KEY,
                $iterator
            );
            if ($iterator == 0) {
                break;
            }
            $result = static::$reBloomClient->bloomFilterLoadChunk('copy-filter', $iterator, $data);
            $this->assertTrue($result);
        }

        $copiedData = static::$reBloomClient->bloomFilterMultiExists('copy-filter', ...self::$data);
        foreach ($copiedData as $item) {
            $this->assertTrue($item);
        }
    }

    public function testLoadChunkException(): void
    {
        $this->expectException(ResponseException::class);
        static::$reBloomClient->bloomFilterLoadChunk('nonexistent', 556, 1);
    }
}
