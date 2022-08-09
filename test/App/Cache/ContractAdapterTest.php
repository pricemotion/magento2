<?php declare(strict_types=1);

namespace Pricemotion\Magento2\App\Cache;

use Symfony\Contracts\Cache\CacheInterface;
use Psr\Cache\CacheItemInterface;

class ContractAdapterTest extends \PHPUnit\Framework\TestCase {
    private $frontend;

    private $adapter;

    public function setUp(): void {
        $this->frontend = new \Magento\Framework\Cache\Frontend\Adapter\Zend(function () {
            return new \Zend_Cache_Backend_File();
        });
        $this->adapter = new ContractAdapter($this->frontend);
    }

    public function testGet(): void {
        $key = uniqid();

        $result = $this->adapter->get($key, function (CacheItemInterface $item): string {
            return 'Hello, World!';
        });
        $this->assertSame('Hello, World!', $result);

        $result = $this->adapter->get($key, function (CacheItemInterface $item): string {
            throw new \Exception('This should not run');
        });
        $this->assertSame('Hello, World!', $result);

        $result = $this->adapter->get(
            $key,
            function (CacheItemInterface $item): string {
                return 'new value';
            },
            \INF,
        );
        $this->assertSame('new value', $result);
    }

    public function testDelete(): void {
        $key = uniqid();

        $this->adapter->get($key, function () {
            return 'Abcd';
        });

        $this->assertTrue($this->adapter->delete($key));

        $this->assertSame(
            'Defg',
            $this->adapter->get($key, function () {
                return 'Defg';
            }),
        );
    }

    /** @dataProvider expiresAtData */
    public function testExpiresAt($method, $input, $expectedExpiry): void {
        $key = uniqid();

        $this->adapter->get($key, function (CacheItemInterface $item) use ($method, $input) {
            $this->assertSame($item, $item->$method($input));
        });

        $this->assertSame($expectedExpiry, unserialize($this->frontend->load($key))['e']);
    }

    public function expiresAtData(): \Generator {
        yield ['expiresAt', null, null];
        yield ['expiresAt', new \DateTimeImmutable('2032-01-01'), strtotime('2032-01-01')];
        yield ['expiresAfter', null, null];
        yield ['expiresAfter', 3600, time() + 3600];
        yield ['expiresAfter', new \DateInterval('PT1H'), time() + 3600];
    }
}
