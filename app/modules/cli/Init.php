<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Cli;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;
use SP\Core\Context\ContextException;
use SP\Core\Context\StatelessContext;
use SP\Core\Language;
use SP\Core\ModuleBase;
use SP\Modules\Cli\Commands\InstallCommand;
use SP\Util\VersionUtil;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Init
 *
 * @package SP\Modules\Cli
 */
final class Init extends ModuleBase
{
    private const CLI_COMMANDS = [
        InstallCommand::class
    ];
    /**
     * @var StatelessContext
     */
    protected $context;
    /**
     * @var Language
     */
    protected $language;
    /**
     * @var Application
     */
    protected $application;

    /**
     * Module constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->context = $container->get(StatelessContext::class);
        $this->language = $container->get(Language::class);
        $this->application = $container->get(Application::class);
    }

    /**
     * @param string $controller
     *
     * @throws ContextException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function initialize(string $controller)
    {
        logger(__FUNCTION__);

        // Initialize context
        $this->context->initialize();

        // Load config
        $this->config->loadConfig();

        // Load language
        $this->language->setLanguage();

        $this->initCli();
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     */
    private function initCli(): void
    {
        $this->application->setName('sysPass CLI');
        $this->application->setVersion(implode('.', VersionUtil::getVersionArray()));

        foreach (self::CLI_COMMANDS as $command) {
            $this->application->add($this->container->get($command));
        }

        $this->application->run(
            $this->container->get(InputInterface::class),
            $this->container->get(OutputInterface::class)
        );
    }
}