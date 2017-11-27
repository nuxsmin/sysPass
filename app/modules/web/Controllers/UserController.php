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
use SP\Core\Acl\ActionsInterface;
use SP\Core\SessionUtil;
use SP\DataModel\UserData;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\User\UserService;

/**
 * Class UserController
 *
 * @package SP\Modules\Web\Controllers
 */
class UserController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * Search action
     */
    public function searchAction()
    {

    }

    /**
     * Create action
     */
    public function createAction()
    {
        // TODO: Implement createAction() method.
    }

    /**
     * Edit action
     *
     * @param $id
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        $this->view->assign('header', __('Editar Usuario'));
        $this->view->assign('isView', false);
        $this->view->assign('isDisabled');
        $this->view->assign('isReadonly');

        try {
            $this->setViewData($id);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponse(0, '', ['html' => $this->render()]);
    }

    /**
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id)
    {
        // TODO: Implement deleteAction() method.
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        // TODO: Implement saveCreateAction() method.
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {
        // TODO: Implement saveEditAction() method.
    }

    /**
     * Saves delete action
     *
     * @param $id
     */
    public function saveDeleteAction($id)
    {
        // TODO: Implement saveDeleteAction() method.
    }

    /**
     * View action
     *
     * @param $id
     */
    public function viewAction($id)
    {
        $this->view->assign('header', __('Ver Usuario'));
        $this->view->assign('isView', true);
        $this->view->assign('isDisabled', 'disabled');
        $this->view->assign('isReadonly', 'readonly');

        try {
            $this->setViewData($id);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponse(0, '', ['html' => $this->render()]);
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $userId
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected function setViewData($userId)
    {
        $this->view->addTemplate('users', 'itemshow');

        $this->view->assign('user', $userId ? $this->userService->getById($userId) : new UserData());
        $this->view->assign('groups', $this->getUserGroups());
        $this->view->assign('profiles', $this->getUserProfiles());
        $this->view->assign('isUseSSO', $this->configData->isAuthBasicAutoLoginEnabled());
        $this->view->assign('sk', SessionUtil::getSessionKey(true));

        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::USER, $userId));
    }

    /**
     * Initialize class
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->userService = new UserService();
    }
}