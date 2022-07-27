<?php
namespace Pricemotion\Magento2\Cron;

use Pricemotion\Magento2\App\Config;
use Pricemotion\Magento2\App\ConfigurationException;
use Pricemotion\Magento2\App\ProductUpdater;
use Pricemotion\Magento2\App\StoreViewEmulator;
use Pricemotion\Magento2\Logger\Logger;
use Pricemotion\Magento2\Model\ProductRepository;
use Throwable;

class Update {
    private const UPDATE_INTERVAL = 3600 * 12;

    private $logger;

    private $config;

    private $priceAttribute;

    private $listPriceAttribute;

    private $ignoreUpdatedAt = false;

    private $timeLimit = 55;

    private $eanFilter = null;

    private $productRepository;

    private $emulator;

    private $updater;

    public function __construct(
        Logger $logger,
        Config $config,
        ProductRepository $productRepository,
        StoreViewEmulator $emulator,
        ProductUpdater $updater
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->productRepository = $productRepository;
        $this->emulator = $emulator;
        $this->updater = $updater;
    }

    public function setIgnoreUpdatedAt(bool $value): void {
        $this->ignoreUpdatedAt = $value;
    }

    public function setTimeLimit(?int $timeLimit): void {
        $this->timeLimit = $timeLimit;
    }

    public function setEanFilter(?array $eanFilter): void {
        $this->eanFilter = $eanFilter;
    }

    public function execute(): void {
        try {
            $this->emulator->emulate(function () {
                $this->doExecute();
            });
        } catch (ConfigurationException $e) {
            $this->logger->error($e->getMessage());
            return;
        } catch (Throwable $e) {
            $this->logger->critical(
                sprintf('Uncaught exception %s: (%d) %s', get_class($e), $e->getCode(), $e->getMessage()),
            );
            $this->logger->critical((string) $e);
            throw $e;
        }
    }

    private function doExecute(): void {
        if ($this->timeLimit === null) {
            $run_until = null;
        } else {
            $run_until = time() + $this->timeLimit;
        }

        $this->priceAttribute = $this->config->getPriceAttribute();
        $this->listPriceAttribute = $this->config->getListPriceAttribute();

        $this->logger->debug("EAN attribute: {$this->config->requireEanAttribute()}");
        $this->logger->debug("Price attribute: {$this->priceAttribute}");
        $this->logger->debug("List price attribute: {$this->listPriceAttribute}");

        if ($this->eanFilter !== null) {
            $products = $this->productRepository->getByEans($this->eanFilter);
            if ($this->ignoreUpdatedAt) {
                $this->logger->warning("The `force' option is superfluous when selecting EANs to be updated");
            }
        } elseif ($this->ignoreUpdatedAt) {
            $products = $this->productRepository->getAll();
        } else {
            $products = $this->productRepository->getForUpdate(self::UPDATE_INTERVAL);
        }

        if (!$products) {
            $this->logger->info('There are no products that need updating');
            return;
        }

        $this->logger->info(sprintf('Got %d products for update', sizeof($products)));

        shuffle($products);

        $processed = 0;
        foreach ($products as $product) {
            $this->logger->debug(
                sprintf(
                    'Product %d: %s',
                    $product->getId(),
                    json_encode($product->getData(), JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                ),
            );

            $this->updater->update($product);

            $processed++;

            if ($run_until !== null && time() > $run_until) {
                $this->logger->info(sprintf('Ran out of time after processing %d products', $processed));
                return;
            }
        }
    }
}
