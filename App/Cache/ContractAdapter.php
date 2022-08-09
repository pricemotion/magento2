<?php declare(strict_types=1);

namespace Pricemotion\Magento2\App\Cache;

use Symfony\Contracts\Cache\CacheInterface;
use Magento\Framework\Cache\FrontendInterface;
use Psr\Cache\CacheItemInterface;

class ContractAdapter implements CacheInterface {
    private $cache;

    public function __construct(FrontendInterface $cache) {
        $this->cache = $cache;
    }

    public function get(string $key, callable $callback, float $beta = null, array &$metadata = null) {
        $item = new Item($this->cache, $key);

        if ($item->isHit() && $beta < \INF) {
            return $item->get();
        }

        $value = $callback($item);
        $item->set($value);
        $item->flush();

        return $value;
    }

    public function delete(string $key): bool {
        $this->get(
            $key,
            function (CacheItemInterface $item) {
                $item->expiresAfter(0);
                return $item->get();
            },
            \INF,
        );
        return true;
    }
}
