<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests;

use DI\ContainerBuilder;
use Doctrine\Common\Cache\ArrayCache;
use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;
use SP\DataModel\ProfileData;
use SP\Services\User\UserLoginResponse;
use SP\Storage\Database\DatabaseConnectionData;

define('APP_MODULE', 'tests');

define('APP_ROOT', dirname(__DIR__));
define('TEST_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests');
define('RESOURCE_DIR', TEST_ROOT . DIRECTORY_SEPARATOR . 'res');
define('CONFIG_PATH', RESOURCE_DIR . DIRECTORY_SEPARATOR . 'config');
define('CONFIG_FILE', CONFIG_PATH . DIRECTORY_SEPARATOR . 'config.xml');
define('ACTIONS_FILE', CONFIG_PATH . DIRECTORY_SEPARATOR . 'actions.xml');
define('CACHE_PATH', RESOURCE_DIR . DIRECTORY_SEPARATOR . 'cache');

require APP_ROOT . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require APP_ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'BaseFunctions.php';

/**
 * Función para llamadas a gettext
 */
if (!function_exists('gettext')) {
    /**
     * @param $str
     *
     * @return string
     */
    function gettext($str)
    {
        return $str;
    }
}

/**
 * Configura el contexto de la aplicación para los tests
 *
 * @throws \DI\DependencyException
 * @throws \DI\NotFoundException
 * @throws \SP\Core\Context\ContextException
 * @return \DI\Container
 */
function setupContext()
{
    // Instancia del contenedor de dependencias con las definiciones de los objetos necesarios
    // para la aplicación
    $builder = new ContainerBuilder();
    $builder->setDefinitionCache(new ArrayCache());
    $builder->addDefinitions(APP_ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Definitions.php');
    $dic = $builder->build();

    // Inicializar el contexto
    $context = $dic->get(ContextInterface::class);
    $context->initialize();
    $context->setConfig(new ConfigData());
    $context->setTrasientKey('_masterpass', '12345678900');


    $userData = new UserLoginResponse();
    $userData->setId(1);
    $userData->setUserGroupId(1);
    $userData->setIsAdminApp(1);
    $userData->setLastUpdate(time());

    $context->setUserData($userData);

    $context->setUserProfile(new ProfileData());

    // Establecer configuración de conexión con la BBDD
    $databaseConnectionData = (new DatabaseConnectionData())
        ->setDbHost('syspass-db')
        ->setDbName('syspass')
        ->setDbUser('root')
        ->setDbPass('syspass');

    // Inicializar la configuración
    $dic->set(ConfigData::class, $context->getConfig());

    // Inicializar los datos de conexión a la BBDD
    $dic->set(DatabaseConnectionData::class, $databaseConnectionData);

    return $dic;
}

/**
 * @param $dir
 * @param $file
 *
 * @return string
 */
function getResource($dir, $file)
{
    return file_get_contents(RESOURCE_DIR . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file) ?: '';
}