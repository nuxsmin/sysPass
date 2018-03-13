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

namespace SP\Modules\Web\Controllers;

defined('APP_ROOT') || die();

use SP\Account\AccountUtil;
use SP\Core\DiFactory;
use SP\Core\Exceptions\SPException;
use SP\Core\Upgrade\Check;
use SP\Http\Request;
use SP\Services\Task\Task;
use SP\Util\Util;

/**
 * Clase encargada de mostrar el interface principal de la aplicación
 * e interfaces que requieren de un documento html completo
 *
 * @package Controller
 */
class MainController
{

    /**
     * Obtener los datos para el interface de actualización de componentes
     *
     * @param $version
     */
    public function getUpgrade($version)
    {
        $this->setPage('upgrade');

        $this->view->addTemplate('body-header');
        $this->view->addTemplate('upgrade');
        $this->view->addTemplate('body-footer');
        $this->view->addTemplate('body-end');

        $action = Request::analyze('a');
        $type = Request::analyze('type');

        $this->view->assign('action', $action);
        $this->view->assign('type', $type);
        $this->view->assign('version', $version);
        $this->view->assign('upgradeVersion', Util::getVersionStringNormalized());
        $this->view->assign('taskId', Task::genTaskId('masterpass'));

        if (Util::checkVersion($version, '130.16011001')) {
            $this->view->assign('checkConstraints', Check::checkConstraints());

            $constraints = [];

            foreach ($this->view->checkConstraints as $key => $val) {
                if ($val > 0) {
                    $constraints[] = sprintf('%s : %s', $key, $val);
                }
            }

            $this->view->assign('constraints', $constraints);
        }

        if (Util::checkVersion($version, '210.17022601')) {
            $this->view->assign('numAccounts', AccountUtil::getTotalNumAccounts());
        }

        $this->view();
        exit();
    }

    /**
     * Obtener los datos para el interface de comprobación de actualizaciones
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function getCheckUpdates()
    {
        $this->view->addTemplate('update');

        $this->view->assign('hasUpdates', false);
        $this->view->assign('updateStatus', null);

        if ($this->configData->isCheckUpdates()) {
            $updates = Util::checkUpdates();

            if (is_array($updates)) {
                $description = nl2br($updates['description']);
                $version = $updates['version'];

                $this->view->assign('hasUpdates', true);
                $this->view->assign('title', $updates['title']);
                $this->view->assign('url', $updates['url']);
                $this->view->assign('description', sprintf('%s - %s <br><br>%s', __('Descargar nueva versión'), $version, $description));
            } else {
                $this->view->assign('updateStatus', $updates);
            }
        }

        if ($this->configData->isChecknotices()) {
            $notices = Util::checkNotices();
            $numNotices = count($notices);
            $noticesTitle = '';

            if ($notices !== false && $numNotices > 0) {
                $noticesTitle = __('Avisos de sysPass') . '<br>';

                foreach ($notices as $notice) {
                    $noticesTitle .= '<br>' . $notice[0];
                }
            }

            $this->view->assign('numNotices', $numNotices);
            $this->view->assign('noticesTitle', $noticesTitle);
        }
    }

    /**
     * Realizar las acciones del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {
        $this->setPage($type);

        try {
            switch ($type) {
                case 'prelogin.passreset':
                    $this->getPassReset();
                    break;
            }

            DiFactory::getEventDispatcher()->notifyEvent('main.' . $type, $this);
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }
}