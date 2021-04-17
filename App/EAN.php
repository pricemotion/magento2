<?php
namespace Pricemotion\Magento2\App;

use InvalidArgumentException;
use JsonSerializable;

class EAN implements JsonSerializable {
    private $value;

    private function __construct(string $ean) {
        $ean = trim($ean);
        $ean = ltrim($ean, '0');
        if ($ean == '') {
            throw new InvalidArgumentException('EAN is an empty string');
        }
        if (strlen($ean) < 8 || strlen($ean) > 14) {
            throw new InvalidArgumentException('EAN must be between 8 and 14 characters long');
        }
        if (!$this->check($ean)) {
            throw new InvalidArgumentException('EAN check digit is invalid');
        }
        $this->value = $ean;
    }

    private function check(string $ean): bool {
        $chars = str_split($ean);
        $check_digit = (int) array_pop($chars);
        $result = 0;
        foreach (array_reverse($chars) as $i => $c) {
            $multiplier = $i % 2 ? 1 : 3;
            $result += (int) $c * $multiplier;
        }
        $result = (10 - ($result % 10)) % 10;
        return $result === $check_digit;
    }

    public static function fromString(string $ean): self {
        return new self($ean);
    }

    public function __toString() {
        return $this->toString();
    }

    public function toString(): string {
        return $this->value;
    }

    public function jsonSerialize(): string {
        return $this->toString();
    }
}
