<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Util;

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
     *
     * @param string $message Error message.
     *
     * @param string $file    Filename that the error was raised in.
     *
     * @param int    $line    Line number the error was raised at.
     *
     * @throws \ErrorException For the error.
     *
     * @return void
     */
    public static function handle($level, $message, $file, $line)
    {
        // respect error_reporting being disabled
        if (!error_reporting()) {
            return;
        }

        if (ini_get('xdebug.scream')) {
            $message .= "\n\nWarning: You have xdebug.scream enabled, the warning above may be" .
                "\na legitimately suppressed error that you were not supposed to see.";
        }

        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Register error handler.
     *
     * @return void
     */
    public static function register()
    {
        set_error_handler(
            array(
                __CLASS__,
                'handle'
            )
        );
    }
}
