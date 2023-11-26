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

namespace SP\Modules\Cli;

use SP\Core\Application;
use SP\Core\Language;
use SP\Core\ModuleBase;
use SP\Core\ProvidersHelper;
use SP\Domain\Core\LanguageInterface;
use SP\Util\VersionUtil;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Init
 *
 * @package SP\Modules\Cli
 */
final class Init extends ModuleBase
{
    private Language           $language;
    private InputInterface     $input;
    private OutputInterface    $output;
    private ConsoleApplication $consoleApplication;
    private CliCommandHelper   $cliCommandHelper;

    public function __construct(
        Application $application,
        ProvidersHelper $providersHelper,
        LanguageInterface $language,
        ConsoleApplication $consoleApplication,
        InputInterface $input,
        OutputInterface $output,
        CliCommandHelper $cliCommandHelper
    ) {
        $this->language = $language;
        $this->consoleApplication = $consoleApplication;
        $this->input = $input;
        $this->output = $output;
        $this->cliCommandHelper = $cliCommandHelper;

        parent::__construct(
            $application,
            $providersHelper
        );

    }

    /**
     * @throws \SP\Core\Context\ContextException
     * @throws \Exception
     */
    public function initialize(string $controller): void
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
     * @throws \Exception
     */
    private function initCli(): void
    {
        $this->consoleApplication->setName('sysPass CLI');
        $this->consoleApplication->setVersion(implode('.', VersionUtil::getVersionArray()));
        $this->consoleApplication->addCommands($this->cliCommandHelper->getCommands());

        $this->consoleApplication->run(
            $this->input,
            $this->output
        );
    }
}
