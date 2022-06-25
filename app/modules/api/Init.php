<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Api;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use SP\Core\Context\ContextException;
use SP\Core\Context\StatelessContext;
use SP\Core\Exceptions\InitializationException;
use SP\Core\Language;
use SP\Core\ModuleBase;
use SP\Services\Upgrade\UpgradeAppService;
use SP\Services\Upgrade\UpgradeDatabaseService;
use SP\Services\Upgrade\UpgradeUtil;
use SP\Storage\Database\DatabaseUtil;
use SP\Storage\File\FileException;
use SP\Util\HttpUtil;

/**
 * Class Init
 *
 * @package api
 */
final class Init extends ModuleBase
{
    /**
     * @var StatelessContext
     */
    protected $context;
    /**
     * @var Language
     */
    protected $language;

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
    }

    /**
     * @param string $controller
     *
     * @throws ContextException
     * @throws InitializationException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws EnvironmentIsBrokenException
     * @throws FileException
     */
    public function initialize($controller)
    {
        logger(__FUNCTION__);

        // Initialize context
        $this->context->initialize();

        // Load config
        $this->config->loadConfig();

        // Load language
        $this->language->setLanguage();

        // Checks if it needs to switch the request over HTTPS
        HttpUtil::checkHttps($this->configData, $this->request);

        // Checks if sysPass is installed
        $this->checkInstalled();

        // Checks if maintenance mode is turned on
        if ($this->checkMaintenanceMode($this->context)) {
            throw new InitializationException('Maintenance mode');
        }

        // Checks if upgrade is needed
        $this->checkUpgrade();

        $databaseUtil = $this->container->get(DatabaseUtil::class);

        // Checks if the database is set up
        if (!$databaseUtil->checkDatabaseConnection()) {
            throw new InitializationException('Database connection error');
        }

        if (!$databaseUtil->checkDatabaseTables($this->configData->getDbName())) {
            throw new InitializationException('Database checking error');
        }

        // Initialize event handlers
        $this->initEventHandlers();
    }

    /**
     * Comprueba que la aplicación esté instalada
     * Esta función comprueba si la aplicación está instalada. Si no lo está, redirige al instalador.
     *
     * @throws InitializationException
     */
    private function checkInstalled()
    {
        if (!$this->configData->isInstalled()) {
            throw new InitializationException('Not installed');
        }
    }

    /**
     * Comprobar si es necesario actualizar componentes
     *
     * @throws EnvironmentIsBrokenException
     * @throws FileException
     * @throws InitializationException
     */
    private function checkUpgrade()
    {
        UpgradeUtil::fixAppUpgrade($this->configData, $this->config);

        if ($this->configData->getUpgradeKey()
            || (UpgradeDatabaseService::needsUpgrade($this->configData->getDatabaseVersion()) ||
                UpgradeAppService::needsUpgrade($this->configData->getAppVersion()))
        ) {
            $this->config->generateUpgradeKey();

            throw new InitializationException(__u('Updating needed'));
        }
    }
}