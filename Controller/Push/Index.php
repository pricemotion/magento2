<?php
namespace Pricemotion\Magento2\Controller\Push;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Pricemotion\Magento2\App\Constants;
use Pricemotion\Magento2\App\Push;
use Throwable;

/** @phan-suppress-next-line PhanDeprecatedClass */
class Index extends Action implements
    HttpGetActionInterface,
    HttpPostActionInterface,
    CsrfAwareActionInterface {
    public function execute() {
        try {
            $result = $this->getActionResponse();
        } catch (Push\Exception $e) {
            http_response_code($e->getHttpResponseCode());
            $result = $e->getResponse();
        }

        header('Content-Type: application/json');
        echo json_encode($result, JSON_PARTIAL_OUTPUT_ON_ERROR);
        exit;
    }

    private function getActionResponse() {
        $input = trim(file_get_contents('php://input'));
        if (!$input) {
            throw new Push\BadRequestException('Request body is empty');
        }

        $input = base64_decode($input, true);
        if (!$input) {
            throw new Push\BadRequestException('Request body is not valid base64');
        }

        $input = sodium_crypto_sign_open($input, base64_decode(Constants::PUBKEY_SIGN));
        if (!$input) {
            throw new Push\BadRequestException('Request body signature is invalid');
        }

        $input = json_decode($input, true);
        if (!is_array($input)) {
            throw new Push\BadRequestException('Request body does not decode as JSON object');
        }

        if (empty($input['expires_at'])) {
            throw new Push\BadRequestException('Request body is missing expires_at');
        }

        if ($input['expires_at'] > time() + 86400) {
            throw new Push\BadRequestException(
                'Request expiry is too far into the future; ' .
                'server time is ' . gmdate('Y-m-d H:m:s')
            );
        }

        if ($input['expires_at'] < time()) {
            throw new Push\BadRequestException(
                'Request has expired; ' .
                'server time is ' . gmdate('Y-m-d H:m:s')
            );
        }

        if (empty($input['action'])) {
            throw new Push\BadRequestException('Request is missing action');
        }

        $cls = 'Pricemotion\\Magento2\\App\\Push\\Action\\' .
            preg_replace_callback(
                '~(?:^|_)([a-z])~i',
                function ($match) {
                    return strtoupper($match[1]);
                },
                $input['action']
            );

        if (!class_exists($cls)) {
            throw new Push\BadRequestException('Unknown action');
        }

        $action = new $cls();

        if (!$action instanceof Push\Action) {
            throw new Push\BadRequestException('Invalid action');
        }

        try {
            return $action->execute($input);
        } catch (Throwable $e) {
            throw new Push\InternalException(
                'Unhandled exception occurred during request processing',
                0,
                $e
            );
        }
    }

    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool {
        return true;
    }
}
