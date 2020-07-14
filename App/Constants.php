<?php
namespace Pricemotion\Magento2\App;

class Constants {

    const VERSION = '$VERSION$';
    const ATTR_LOWEST_PRICE = 'pricemotion_lowest_price';
    const ATTR_LOWEST_PRICE_RATIO = 'pricemotion_lowest_price_ratio';
    const ATTR_UPDATED_AT = 'pricemotion_updated_at';
    const ATTR_SETTINGS = 'pricemotion_settings';

    public static function isDevelopmentVersion() {
        return self::VERSION == '$VER' . 'SION$';
    }

    public static function getAssetVersion() {
        if (self::isDevelopmentVersion()) {
            return $_SERVER['REQUEST_TIME'];
        } else {
            return self::VERSION;
        }
    }

    public static function getWebUrl() {
        if (self::isDevelopmentVersion()) {
            return 'http://localhost:8080';
        } else {
            return 'https://www.pricemotion.nl/app';
        }
    }

}