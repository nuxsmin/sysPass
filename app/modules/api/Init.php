<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\Container;
use Interop\Container\Exception\NotFoundException;
use SP\Core\Context\StatelessContext;
use SP\Core\Exceptions\InitializationException;
use SP\Core\Language;
use SP\Core\ModuleBase;
use SP\Services\Upgrade\UpgradeAppService;
use SP\Services\Upgrade\UpgradeDatabaseService;
use SP\Services\Upgrade\UpgradeUtil;
use SP\Storage\Database\Database;
use SP\Storage\Database\DBUtil;
use SP\Util\HttpUtil;

/**
 * Class Init
 *
 * @package api
 */
class Init extends ModuleBase
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
     * @param Container $container
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->context = $container->get(StatelessContext::class);
        $this->language = $container->get(Language::class);
    }

    /**
     * @param string $controller
     * @throws InitializationException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\SPException
     * @throws NotFoundException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function initialize($controller)
    {
        debugLog(__FUNCTION__);

        // Initialize context
        $this->context->initialize();

        // Load config
        $this->config->loadConfig($this->context);

        // Load language
        $this->language->setLanguage();

        // Checks if it needs to switch the request over HTTPS
        HttpUtil::checkHttps($this->configData, $this->request);

        // Checks if sysPass is installed
        if (!$this->checkInstalled()) {
            throw new InitializationException('Not installed');
        }

        // Checks if maintenance mode is turned on
        $this->checkMaintenanceMode($this->context);

        // Checks if upgrade is needed
        $this->checkUpgrade();

        // Checks if the database is set up
        DBUtil::checkDatabaseExist($this->container->get(Database::class)->getDbHandler(), $this->configData->getDbName());

        // Initialize event handlers
        $this->initEventHandlers();
    }

    /**
     * Comprueba que la aplicación esté instalada
     * Esta función comprueba si la aplicación está instalada. Si no lo está, redirige al instalador.
     */
    private function checkInstalled()
    {
        return $this->configData->isInstalled();
    }

    /**
     * Comprobar si es necesario actualizar componentes
     *
     * @throws InitializationException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function checkUpgrade()
    {
        if ($this->configData->getUpgradeKey()
            || (UpgradeDatabaseService::needsUpgrade($this->configData->getDatabaseVersion()) ||
                UpgradeAppService::needsUpgrade(UpgradeUtil::fixVersionNumber($this->configData->getConfigVersion())))
        ) {
            $this->config->generateUpgradeKey();

            throw new InitializationException(__u('Es necesario actualizar'));
        }
    }
}