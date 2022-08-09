<?php declare(strict_types=1);

namespace Pricemotion\Magento2\App\Cache;

use Psr\Cache\CacheItemInterface;
use Magento\Framework\Cache\FrontendInterface;

class Item implements CacheItemInterface {
    private $cache;

    private $key;

    private $isHit = false;

    private $value = null;

    private $expiresAt = null;

    private $updated = false;

    public function __construct(FrontendInterface $cache, string $key) {
        $this->cache = $cache;
        $this->key = $key;
        $value = $this->cache->load($this->key);
        if (strpos((string) $value, 'a:') !== 0) {
            return;
        }
        $value = unserialize($value);
        if (!isset($value['v']) || (isset($value['e']) && (int) $value['e'] <= time())) {
            return;
        }
        $this->isHit = true;
        $this->value = $value['v'];
        $this->expiresAt = !isset($value['e']) ? null : (int) $value['e'];
    }

    public function getKey(): string {
        return $this->key;
    }

    public function get() {
        return $this->isHit ? $this->value : null;
    }

    public function isHit(): bool {
        return $this->isHit;
    }

    public function set($value) {
        $this->updated = true;
        $this->value = $value;
        return $this;
    }

    public function expiresAt($expiration) {
        $this->updated = true;
        if ($expiration === null) {
            $this->expiresAt = null;
        } elseif ($expiration instanceof \DateTimeInterface) {
            $this->expiresAt = $expiration->getTimestamp();
        } else {
            throw new \InvalidArgumentException();
        }
        return $this;
    }

    public function expiresAfter($time) {
        if ($time === null) {
            return $this->expiresAt(null);
        } elseif ($time instanceof \DateInterval) {
            return $this->expiresAt((new \DateTimeImmutable())->add($time));
        } else {
            return $this->expiresAt((new \DateTimeImmutable())->modify(sprintf('+%d seconds', (int) $time)));
        }
    }

    public function flush(): void {
        if (!$this->updated) {
            return;
        }
        $this->cache->save(
            serialize([
                'v' => $this->value,
                'e' => $this->expiresAt,
            ]),
            $this->key,
        );
        $this->updated = false;
    }
}
