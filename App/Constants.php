<?php
namespace Pricemotion\Magento2\App;

class Constants {

    private static $version = '$VERSION$';

    const ATTR_LOWEST_PRICE = 'pricemotion_lowest_price';
    const ATTR_LOWEST_PRICE_RATIO = 'pricemotion_lowest_price_ratio';
    const ATTR_UPDATED_AT = 'pricemotion_updated_at';
    const ATTR_SETTINGS = 'pricemotion_settings';

    public static function getAssetVersion() {
        if (self::isDevelopmentVersion()) {
            return $_SERVER['REQUEST_TIME'];
        } elseif (self::$version != '$VER' . 'SION$') {
            return self::$version;
        } else {
            return self::$version = self::getComposerVersion();
        }
    }

    private static function getComposerVersion() {
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
            if ($package['name'] !== 'pricemotion/module-pricemotion') {
                continue;
            }
            if (empty($package['version'])) {
                continue;
            }
            return preg_replace('/^v/', '', $package['version']);
        }
    }

    public static function getWebUrl() {
        if (self::isDevelopmentVersion()) {
            return 'http://localhost:8080';
        } else {
            return 'https://www.pricemotion.nl/app';
        }
    }

    private static function isDevelopmentVersion() {
        return !!getenv('PRICEMOTION_DEVELOPMENT');
    }

}