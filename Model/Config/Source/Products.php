<?php
namespace Pricemotion\Magento2\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/** @phan-suppress-next-line PhanUnreferencedClass */
class Products implements OptionSourceInterface {
    const VALUE_ALL = 'all';

    const VALUE_WITH_RULES = 'with_rules';

    public function toOptionArray(): array {
        return [
            [
                'value' => self::VALUE_ALL,
                'label' => 'Follow all products that have an EAN',
            ],
            [
                'value' => self::VALUE_WITH_RULES,
                'label' => 'Follow only products that have price rules configured',
            ],
        ];
    }
}
