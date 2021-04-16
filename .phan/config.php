<?php

return [
    'target_php_version' => '7.3',
    'directory_list' => iterator_to_array((function () {
        foreach (scandir(__DIR__ . '/..') as $file) {
            if (is_dir(__DIR__ . '/../' . $file)
                && preg_match('~^[A-Z]~', $file)
            ) {
                yield $file;
            }
        }
        yield 'www/vendor';
    })(), false),
    'exclude_analysis_directory_list' => [
        'www/vendor',
    ],
    'plugins' => [
        'AlwaysReturnPlugin',
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
    ],
    'suppress_issue_types' => [
        'PhanAccessMethodInternal',
        'PhanPluginMixedKeyNoKey',
    ],
];
