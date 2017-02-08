#!/usr/bin/env php
<?php

/**
 * This file is part of cyberspectrum/contao-toolbox.
 *
 * (c) 2013-2017 CyberSpectrum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    cyberspectrum/contao-toolbox.
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2013-2017 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/contao-toolbox/blob/master/LICENSE LGPL-3.0
 * @filesource
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
