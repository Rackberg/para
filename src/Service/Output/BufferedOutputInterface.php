<?php
/**
 * @file
 * Contains Para\Service\Output\BufferedOutputInterface.php.
 */

namespace Para\Service\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface BufferedOutputInterface.
 *
 * @package Para\Service\Output
 */
interface BufferedOutputInterface extends OutputInterface
{
    /**
     * Flushes the buffer.
     *
     * All buffered messages to print on the console gets printed.
     */
    public function flush();
}
