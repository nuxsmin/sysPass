<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace Plugins\Authenticator;

use SP\Core\DiFactory;
use SP\Core\Plugin\PluginBase;
use SplSubject;

/**
 * Class Plugin
 *
 * @package Plugins\Authenticator
 */
class AuthenticatorPlugin extends PluginBase
{
    const PLUGIN_NAME = 'Authenticator';

    /**
     * Receive update from subject
     *
     * @link  http://php.net/manual/en/splobserver.update.php
     * @param SplSubject $subject <p>
     *                            The <b>SplSubject</b> notifying the observer of an update.
     *                            </p>
     * @return void
     * @since 5.1.0
     */
    public function update(SplSubject $subject)
    {
    }

    /**
     * Inicialización del plugin
     */
    public function init()
    {
        if (!is_array($this->data)) {
            $this->data = [];
        }

        $this->base = __DIR__;
        $this->themeDir = __DIR__ . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . DiFactory::getTheme()->getThemeName();

        $this->setLocales();
    }

    /**
     * Evento de actualización
     *
     * @param string $event Nombre del evento
     * @param mixed  $object
     * @throws \SP\Core\Exceptions\FileNotFoundException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateEvent($event, $object)
    {
        switch ($event){
            case 'user.preferences':
                $Controller = new PreferencesController($object, $this);
                $Controller->getSecurityTab();
                break;
            case 'main.prelogin.2fa':
                $Controller = new LoginController($this);
                $Controller->get2FA($object);
                break;
            case 'login.preferences':
                $Controller = new LoginController($this);
                $Controller->checkLogin();
                break;
        }
    }

    /**
     * Devuelve los eventos que implementa el observador
     *
     * @return array
     */
    public function getEvents()
    {
        return ['user.preferences', 'main.prelogin.2fa', 'login.preferences'];
    }

    /**
     * Devuelve los recursos JS y CSS necesarios para el plugin
     *
     * @return array
     */
    public function getJsResources()
    {
        return ['plugin.min.js'];
    }

    /**
     * Devuelve el autor del plugin
     *
     * @return string
     */
    public function getAuthor()
    {
        return 'Rubén D.';
    }

    /**
     * Devuelve la versión del plugin
     *
     * @return array
     */
    public function getVersion()
    {
        return [1, 0];
    }

    /**
     * Devuelve la versión compatible de sysPass
     *
     * @return array
     */
    public function getCompatibleVersion()
    {
        return [2, 0];
    }

    /**
     * Devuelve los recursos CSS necesarios para el plugin
     *
     * @return array
     */
    public function getCssResources()
    {
        return [];
    }

    /**
     * Devuelve el nombre del plugin
     *
     * @return string
     */
    public function getName()
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @return array|AuthenticatorData[]
     */
    public function getData()
    {
        return (array)parent::getData();
    }
}