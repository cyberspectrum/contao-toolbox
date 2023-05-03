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

namespace CyberSpectrum\ContaoToolBox\Util;

use ErrorException;

/**
 * Convert PHP errors into exceptions.
 *
 * @author Artem Lopata <biozshock@gmail.com>
 */
class ErrorHandler
{
    /**
     * Error handler.
     *
     * @param int    $level   Level of the error raised.
     * @param string $message Error message.
     * @param string $file    Filename that the error was raised in.
     * @param int    $line Line number the error was raised at.
     *
     * @throws ErrorException For the error.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function handle(int $level, string $message, string $file, int $line): void
    {
        // respect error_reporting being disabled
        if (!error_reporting()) {
            return;
        }

        if (ini_get('xdebug.scream')) {
            $message .= "\n\nWarning: You have xdebug.scream enabled, the warning above may be" .
                "\na legitimately suppressed error that you were not supposed to see.";
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Register error handler.
     */
    public static function register(): void
    {
        /** @psalm-suppress InvalidArgument */
        set_error_handler([ErrorHandler::class, 'handle']);
    }
}
