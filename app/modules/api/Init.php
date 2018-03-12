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
use SP\Core\Context\ApiContext;
use SP\Core\Exceptions\InitializationException;
use SP\Core\Language;
use SP\Core\ModuleBase;
use SP\Storage\Database;
use SP\Storage\DBUtil;
use SP\Util\HttpUtil;

/**
 * Class Init
 * @package api
 */
class Init extends ModuleBase
{
    /**
     * @var ApiContext
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

        $this->context = $container->get(ApiContext::class);
        $this->language = $container->get(Language::class);
    }

    /**
     * @param $controller
     * @throws InitializationException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function initialize($controller)
    {
        debugLog(__FUNCTION__);

        // Cargar la configuración
        $this->config->loadConfig($this->context);

        // Cargar el lenguaje
        $this->language->setLanguage();

        // Comprobar si es necesario cambiar a HTTPS
        HttpUtil::checkHttps($this->configData);

        // Comprobar si está instalado
        if (!$this->checkInstalled()) {
            throw new InitializationException('Not installed');
        }

        // Comprobar si el modo mantenimiento está activado
        $this->checkMaintenanceMode($this->context);

        // Comprobar si la Base de datos existe
        DBUtil::checkDatabaseExist($this->container->get(Database::class)->getDbHandler(), $this->configData->getDbName());

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
}