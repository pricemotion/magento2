<?php
$vendorDirectories = [
    'www/vendor',
    'www/generated',
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
    'exclude_analysis_directory_list' => $vendorDirectories,
    'plugins' => [
        'AlwaysReturnPlugin',
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
    ],
];
