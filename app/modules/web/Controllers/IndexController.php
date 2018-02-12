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

namespace SP\Modules\Web\Controllers;

use SP\Controller\ControllerBase;
use SP\Http\Response;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Util\Util;

/**
 * Class IndexController
 *
 * @package SP\Modules\Web\Controllers
 */
class IndexController extends ControllerBase
{
    /**
     * Index action
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function indexAction()
    {
        if (!$this->session->isLoggedIn()) {
            Response::redirect('index.php?r=login');
        } else {
            $LayoutHelper = new LayoutHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
            $LayoutHelper->getFullLayout('main', $this->acl);

            $this->view();
        }
    }

    /**
     * Updates checking action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function checkUpdatesAction()
    {
        $this->checkLoggedIn();
        
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
}