#!/usr/bin/env php
<?php
/*
 * This file is part of ctb.
 *
 * For the full copyright and license information, please view
 * the license that is located at the bottom of this file.
 */

if (version_compare(phpversion(), '@@CTB_MIN_PHP_VERSION@@', '<=')) {
    fwrite(
        STDERR,
        'Error: Need at least PHP @@CTB_MIN_PHP_VERSION@@ while you have ' . phpversion() . PHP_EOL
    );
    exit;
}

Phar::mapPhar('ctb.phar');

if (false && '@WARNING_TIME@') {
    fwrite(
        STDERR,
        sprintf(
            'Warning: This build is over 30 days old. ' .
            'It is recommended to update it by running "%s self-update" to get the latest version.' . PHP_EOL,
            $_SERVER['PHP_SELF']
        )
    );
}

require 'phar://ctb.phar/bin/ctb';

__HALT_COMPILER();
