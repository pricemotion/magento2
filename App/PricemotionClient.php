<?php
namespace Pricemotion\Magento2\App;

class PricemotionClient {
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function getProduct(EAN $ean): Product {
        $result = $this->get('/service/', [
            'token' => $this->config->getApiToken(),
            'ean' => $ean->toString(),
        ]);

        $document = new \DOMDocument();

        if (!$document->loadXML($result)) {
            throw new \RuntimeException("API response is not valid XML");
        }

        // TODO: Handle errors
        // TODO: Return Product
    }

    private function get(string $path, array $params) {
        $path = $path . '?' . http_build_query($params);
        return $this->request($path);
    }

    private function post(string $path, array $data): array {
        $json = json_encode($data);
        if ($json === false) {
            throw new \RuntimeException("JSON encode failed");
        }
        $result = $this->request($path, [
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);
        return $this->decodeResponse($result);
    }

    private function request(string $path, array $options = []): string {
        $ch = curl_init();
        if (!$ch) {
            throw new \RuntimeException("curl_init failed");
        }
        $options = array_merge([
            CURLOPT_URL => $this->getUrl($path),
            CURLOPT_FAILONERROR => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_RETURNTRANSFER => true,
        ], $options);
        if (!curl_setopt_array($ch, $options)) {
            throw new \RuntimeException(sprintf(
                "curl_setopt_array failed: (%s) %s",
                curl_errno($ch), curl_error($ch)
            ));
        }
        $result = curl_exec($ch);
        if ($result === false) {
            throw new \RuntimeException(sprintf(
                "API request failed: (%s) %s",
                curl_errno($ch), curl_error($ch)
            ));
        }
        return $result;
    }

    private function decodeResponse(string $response): array {
        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new \RuntimeException("API response is not a JSON object: {$response}");
        }
        return $result;
    }

    private function getUrl(string $path): string {
        return "https://www.pricemotion.nl/api";
    }
}