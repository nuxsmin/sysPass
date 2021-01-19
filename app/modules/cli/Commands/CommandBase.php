<?php

namespace SP\Modules\Cli\Commands;

use Psr\Log\LoggerInterface;
use SP\Config\Config;
use SP\Config\ConfigData;
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
     * @var Config
     */
    protected $config;
    /**
     * @var ConfigData
     */
    protected $configData;

    /**
     * CommandBase constructor.
     *
     * @param LoggerInterface $logger
     * @param SymfonyStyle    $io
     * @param Config          $config
     */
    public function __construct(
        LoggerInterface $logger,
        SymfonyStyle $io,
        Config $config)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->io = $io;
        $this->config = $config;
        $this->configData = $this->config->getConfigData();
    }
}