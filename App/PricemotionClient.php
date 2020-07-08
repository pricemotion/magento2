<?php
namespace Pricemotion\Magento2\App;

class PricemotionClient {
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @param string[] $eans
     */
    public function subscribe(array $eans): void {
        $this->post('/subscribe', [
            'eans' => $eans,
        ]);
    }

    private function post($path, array $data): array {
        $json = json_encode($data);
        if ($json === false) {
            throw new \RuntimeException("JSON encode failed");
        }
        $ch = curl_init();
        if (!$ch) {
            throw new \RuntimeException("curl_init failed");
        }
        if (!curl_setopt_array($ch, [
            CURLOPT_URL => $this->getUrl($path),
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_FAILONERROR => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_RETURNTRANSFER => true,
        ])) {
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
        return $this->decodeResponse($result);
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