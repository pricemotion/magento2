<?php
namespace Pricemotion\Magento2\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class LowestPriceRatio extends Column {
    public function prepareDataSource(array $dataSource) {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $field = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($item[$field])) {
                $item[$field] = $this->formatValue((float) $item[$field]);
            }
        }

        return $dataSource;
    }

    private function formatValue(float $value): string {
        /** @phan-suppress-next-line PhanPluginPrintfNotPercent */
        return sprintf('%+.1f%%', ($value - 1) * 100);
    }
}
