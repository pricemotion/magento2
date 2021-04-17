<?php

$vendorDirectories = [
    '.phan/stubs',
    'www/generated/code/Magento/Catalog/Model/ResourceModel/Product',
    'www/vendor/magento/framework',
    'www/vendor/magento/module-backend',
    'www/vendor/magento/module-catalog',
    'www/vendor/magento/module-csp',
    'www/vendor/magento/module-eav',
    'www/vendor/magento/module-store',
    'www/vendor/magento/module-ui',
    'www/vendor/monolog/monolog',
    'www/vendor/symfony/console',
];

return [
    'target_php_version' => '7.3',
    'directory_list' => iterator_to_array((function () use ($vendorDirectories) {
        foreach (scandir(__DIR__ . '/..') as $file) {
            if (is_dir(__DIR__ . '/../' . $file)
                && preg_match('~^[A-Z]~', $file)
            ) {
                yield $file;
            }
        }
        yield from $vendorDirectories;
    })(), false),
    'exclude_file_regex' => '~
        ^www/.*Test\.php$ |
        ^www/.*/Test |
        ^www/.*/__\.php$
    ~x',
    'exclude_analysis_directory_list' => $vendorDirectories,
    'plugins' => [
        'AlwaysReturnPlugin',
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
    ],
    'dead_code_detection' => !in_array('--language-server-on-stdin', $GLOBALS['argv']),
    'unused_variable_detection' => true,
    'redundant_condition_detection' => true,
    'suppress_issue_types' => [
        'PhanUnusedProtectedMethodParameter',
        'PhanUnusedPublicMethodParameter',
        'PhanUnusedVariableCaughtException',
    ],
];
