<?php
namespace Pricemotion\Magento2\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Pricemotion\Sdk\Api\WebhookRequestFactory;
use Pricemotion\Sdk\Crypto\SignatureVerifier;

/** @phan-suppress-next-line PhanDeprecatedClass */
class Index extends Action implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface {
    public function __construct(Context $context, CacheInterface $cacheInterface) {
        parent::__construct($context);
    }

    public function execute(): Response {
        $signatureVerifier = new SignatureVerifier();
        $webhookRequestFactory = new WebhookRequestFactory($signatureVerifier);
        $webhookRequest = $webhookRequestFactory->createFromInput();

        $response = new Response();
        return $response;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool {
        return true;
    }
}
