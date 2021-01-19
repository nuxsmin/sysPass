<?php

namespace SP\Modules\Cli\Commands;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CommandBase
 *
 * @package SPDecrypter\Commands
 */
abstract class CommandBase extends Command
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * CommandBase constructor.
     *
     * @param LoggerInterface $logger
     * @param SymfonyStyle    $io
     */
    public function __construct(LoggerInterface $logger, SymfonyStyle $io)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->io = $io;
    }
}