<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Cli\Commands;

use Psr\Log\LoggerInterface;
use SP\Config\Config;
use SP\Config\ConfigData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class CommandBase
 *
 * @package SP\Modules\Cli\Commands
 */
abstract class CommandBase extends Command
{
    /**
     * @var string[]
     */
    public static array $envVarsMapping = [];
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    /**
     * @var Config
     */
    protected Config $config;
    /**
     * @var ConfigData
     */
    protected ConfigData $configData;

    /**
     * CommandBase constructor.
     *
     * @param LoggerInterface $logger
     * @param Config          $config
     */
    public function __construct(
        LoggerInterface $logger,
        Config          $config
    )
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->configData = $this->config->getConfigData();

        parent::__construct();
    }

    /**
     * @param string         $option
     * @param InputInterface $input
     *
     * @return array|false|mixed|string
     */
    protected static function getEnvVarOrOption(
        string         $option,
        InputInterface $input
    )
    {
        return static::getEnvVarForOption($option)
            ?: $input->getOption($option);
    }

    /**
     * @param string $option
     *
     * @return string|false
     */
    protected static function getEnvVarForOption(string $option)
    {
        return getenv(static::$envVarsMapping[$option]);
    }

    /**
     * @param string         $argument
     * @param InputInterface $input
     *
     * @return array|false|mixed|string
     */
    protected static function getEnvVarOrArgument(
        string         $argument,
        InputInterface $input
    )
    {
        return static::getEnvVarForOption($argument)
            ?: $input->getArgument($argument);
    }
}