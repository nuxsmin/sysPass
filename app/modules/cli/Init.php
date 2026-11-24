<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Modules\Cli;

use Exception;
use SP\Core\ModuleBase;
use SP\Core\ModuleDependencies;
use SP\Core\ProvidersHelper;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Core\Context\ContextException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function SP\logger;

/**
 * Class Init
 *
 * @package SP\Modules\Cli
 */
final class Init extends ModuleBase
{
    private LanguageInterface $language;
    private InputInterface     $input;
    private OutputInterface    $output;
    private ConsoleApplication $consoleApplication;
    private CliCommandHelper   $cliCommandHelper;

    public function __construct(
        ModuleDependencies $dependencies,
        ProvidersHelper    $providersHelper,
        LanguageInterface  $language,
        ConsoleApplication $consoleApplication,
        InputInterface     $input,
        OutputInterface    $output,
        CliCommandHelper                           $cliCommandHelper,
        private readonly MasterPasswordRequestLock $masterPasswordRequestLock
    ) {
        $this->language = $language;
        $this->consoleApplication = $consoleApplication;
        $this->input = $input;
        $this->output = $output;
        $this->cliCommandHelper = $cliCommandHelper;

        parent::__construct(
            $dependencies,
            $providersHelper
        );
    }

    /**
     * @throws ContextException
     * @throws Exception
     */
    public function initialize(string $controller): void
    {
        logger(__FUNCTION__);

        // Initialize context
        $this->context->initialize();

        // Load language
        $this->language->setLanguage();

        $this->consoleApplication->addCommands($this->cliCommandHelper->getCommands());
        $commandName = $this->input->getFirstArgument();
        try {
            $resolvedName = $commandName === null ? null : $this->consoleApplication->find($commandName)->getName();
        } catch (ExceptionInterface) {
            $resolvedName = null;
        }
        $requiresLock = $this->configData->isInstalled()
            && $resolvedName !== 'sp:crypt:update-master-password';
        if ($requiresLock) {
            $this->masterPasswordRequestLock->acquire();
        }
        try {
            $this->initEventHandlers();
            $this->initCli();
        } finally {
            if ($requiresLock) {
                $this->masterPasswordRequestLock->release();
            }
        }
    }

    public function getName(): string
    {
        return 'cli';
    }

    /**
     * @throws Exception
     */
    private function initCli(): void
    {
        $this->consoleApplication->setName('sysPass CLI');
        $this->consoleApplication->setVersion(implode('.', Version::getVersionArray()));
        $this->consoleApplication->run(
            $this->input,
            $this->output
        );
    }
}
