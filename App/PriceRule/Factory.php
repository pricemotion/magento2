<?php
namespace Pricemotion\Magento2\App\PriceRule;

class Factory {
    private const RULE_CLASSES = [
        'disabled' => Disabled::class,
        'percentageBelowAverage' => PercentageBelowAverage::class,
        'equalToPosition' => EqualToPosition::class,
        'lessThanPosition' => LessThanPosition::class,
    ];

    public function fromJson(string $json): PriceRuleInterface {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException("Price rule JSON is invalid");
        }
        if (empty($data['rule'])) {
            throw new \InvalidArgumentException("Price rule JSON misses rule element");
        }
        if (!isset(self::RULE_CLASSES[$data['rule']])) {
            throw new \InvalidArgumentException("Invalid price rule: {$data['rule']}");
        }
        $class = new \ReflectionClass(self::RULE_CLASSES[$data['rule']]);
        if ($class->getConstructor()->getNumberOfRequiredParameters() == 0) {
            $rule = $class->newInstance();
        } elseif (!isset($data[$data['rule']])) {
            throw new \InvalidArgumentException("Required parameter missing for rule: {$data['rule']}");
        } else {
            $rule = $class->newInstance($data[$data['rule']]);
        }
        if (!$rule instanceof PriceRuleInterface) {
            throw new \LogicException("{$class->getName()} must implement PriceRuleInterface");
        }
        return $rule;
    }
}