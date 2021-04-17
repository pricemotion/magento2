<?php
namespace Pricemotion\Magento2\App\Push\Action;

use Pricemotion\Magento2\App\ProductUpdater;
use Pricemotion\Magento2\App\Push\Action;
use Pricemotion\Magento2\App\Push\BadRequestException;
use Pricemotion\Magento2\App\StoreViewEmulator;
use Pricemotion\Magento2\Model\ProductRepository;

class UpdateProduct implements Action {
    private $productRepository;

    private $updater;

    private $emulator;

    public function __construct(
        ProductRepository $productRepository,
        ProductUpdater $updater,
        StoreViewEmulator $emulator
    ) {
        $this->productRepository = $productRepository;
        $this->updater = $updater;
        $this->emulator = $emulator;
    }

    public function execute(array $request): array {
        return $this->emulator->emulate(function () use ($request): array {
            if (isset($request['ean'])) {
                $products = $this->productRepository->getByEans([$request['ean']]);
            } else {
                throw new BadRequestException('Missing ean parameter');
            }

            $ids = [];

            foreach ($products as $product) {
                $this->updater->update($product);
                $ids[] = (int) $product->getId();
            }

            return ['updatedProductIds' => $ids];
        });
    }
}
