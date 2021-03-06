<?php
namespace Pricemotion\Magento2\App\Push\Action;

use Pricemotion\Magento2\App\Push\Action;
use Pricemotion\Magento2\App\StoreViewEmulator;
use Pricemotion\Magento2\Model\Attributes;
use Pricemotion\Magento2\Model\ProductRepository;

class ListProducts implements Action {
    private $productRepository;

    private $emulator;

    private $eanAttribute;

    private $priceAttribute;

    private $listPriceAttribute;

    private $settingsAttribute;

    private $updatedAtAttribute;

    private $lowestPriceAttribute;

    private $lowestPriceRatioAttribute;

    public function __construct(
        ProductRepository $productRepository,
        StoreViewEmulator $emulator,
        Attributes\Ean $eanAttribute,
        Attributes\Price $priceAttribute,
        Attributes\ListPrice $listPriceAttribute,
        Attributes\Settings $settingsAttribute,
        Attributes\UpdatedAt $updatedAtAttribute,
        Attributes\LowestPrice $lowestPriceAttribute,
        Attributes\LowestPriceRatio $lowestPriceRatioAttribute
    ) {
        $this->productRepository = $productRepository;
        $this->emulator = $emulator;
        $this->eanAttribute = $eanAttribute;
        $this->priceAttribute = $priceAttribute;
        $this->listPriceAttribute = $listPriceAttribute;
        $this->settingsAttribute = $settingsAttribute;
        $this->updatedAtAttribute = $updatedAtAttribute;
        $this->lowestPriceAttribute = $lowestPriceAttribute;
        $this->lowestPriceRatioAttribute = $lowestPriceRatioAttribute;
    }

    public function execute(array $request): array {
        $result = [];

        $this->emulator->emulate(function () use (&$result) {
            foreach ($this->productRepository->getAll() as $product) {
                $result[] = [
                    'id' => (int) $product->getId(),
                    'ean' => $this->eanAttribute->get($product),
                    'price' => $this->priceAttribute->get($product),
                    'listPrice' => $this->listPriceAttribute->get($product),
                    'settings' => $this->settingsAttribute->get($product),
                    'updatedAt' => $this->updatedAtAttribute->get($product),
                    'lowestPrice' => $this->lowestPriceAttribute->get($product),
                    'lowestPriceRatio' => $this->lowestPriceRatioAttribute->get($product),
                ];
            }
        });

        return ['products' => $result];
    }
}
