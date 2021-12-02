<?php
namespace Pricemotion\Magento2\App;

class Constants {
    private static $version = '$VERSION$';

    const ATTR_LOWEST_PRICE = 'pricemotion_lowest_price';

    const ATTR_LOWEST_PRICE_RATIO = 'pricemotion_lowest_price_ratio';

    const ATTR_UPDATED_AT = 'pricemotion_updated_at';

    const ATTR_SETTINGS = 'pricemotion_settings';

    const PUBKEY_SIGN = 'jgv3KkVIW4VzKbq8g6fs/XvhZk56BqJeU2/ch2tqm7k=';

    public static function getAssetVersion() {
        if (self::isDevelopmentVersion()) {
            return $_SERVER['REQUEST_TIME'];
        }
        return self::getVersion();
    }

    public static function getVersion() {
        if (self::$version != '$VER' . 'SION$') {
            return self::$version;
        }
        return self::$version = self::getComposerVersion();
    }

    private static function getComposerVersion(): ?string {
        $manifest = __DIR__ . '/../../../composer/installed.json';
        if (!is_file($manifest)) {
            return null;
        }
        $contents = file_get_contents($manifest);
        if ($contents === false) {
            return null;
        }
        $data = json_decode($contents, true);
        if (!is_array($data)) {
            return null;
        }
        foreach ($data as $package) {
            if (empty($package['name']) || empty($package['version'])) {
                continue;
            }
            if ($package['name'] !== 'pricemotion/module-pricemotion') {
                continue;
            }
            return preg_replace('/^v/', '', $package['version']);
        }
        return null;
    }

    public static function getWebUrl() {
        if (self::isDevelopmentVersion()) {
            return 'http://localhost:8080';
        }
        return 'https://www.pricemotion.nl/app';
    }

    private static function isDevelopmentVersion() {
        return !!getenv('PRICEMOTION_DEVELOPMENT');
    }
}
