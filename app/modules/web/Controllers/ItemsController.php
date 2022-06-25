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

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\DataModel\DataModelInterface;
use SP\DataModel\NotificationData;
use SP\Html\Html;
use SP\Http\Json;
use SP\Http\JsonResponse;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Account\AccountService;
use SP\Services\Category\CategoryService;
use SP\Services\Client\ClientService;
use SP\Services\Notification\NotificationService;
use SP\Services\Tag\TagService;
use stdClass;

/**
 * Class ItemsController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ItemsController extends SimpleControllerBase
{
    /**
     * Devolver las cuentas visibles por el usuario
     *
     * @param int $accountId
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function accountsUserAction($accountId = null)
    {
        $outItems = [];

        foreach ($this->dic->get(AccountService::class)->getForUser($accountId) as $account) {
            $obj = new stdClass();
            $obj->id = $account->id;
            $obj->name = $account->clientName . ' - ' . $account->name;

            $outItems[] = $obj;
        }

        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus(0);
        $jsonResponse->setData($outItems);
        $jsonResponse->setCsrf($this->session->getSecurityKey());

        Json::fromDic()->returnJson($jsonResponse);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function clientsAction()
    {
        Json::factory($this->router->response())
            ->returnRawJson(
                SelectItemAdapter::factory(
                    $this->dic->get(ClientService::class)
                        ->getAllForUser())->getJsonItemsFromModel());
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function categoriesAction()
    {
        Json::factory($this->router->response())
            ->returnRawJson(
                SelectItemAdapter::factory(
                    $this->dic->get(CategoryService::class)
                        ->getAllBasic())->getJsonItemsFromModel());
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function notificationsAction()
    {
        $notifications = array_map(
            function ($notification) {
                /** @@var $notification NotificationData */
                return sprintf(
                    '(%s) - %s',
                    $notification->getComponent(),
                    Html::truncate(Html::stripTags($notification->getDescription()), 30)
                );
            }, $this->dic
            ->get(NotificationService::class)
            ->getAllActiveForUserId($this->session->getUserData()->getId()));

        $count = count($notifications);

        $jsonResponse = new JsonResponse();
        $jsonResponse->setStatus(0);
        $jsonResponse->setData([
            'message' => __('There aren\'t any pending notifications'),
            'message_has' => sprintf(__('There are pending notifications: %d'), $count),
            'count' => $count,
            'notifications' => $notifications,
            'hash' => sha1(implode('', $notifications))
        ]);

        Json::factory($this->router->response())
            ->returnJson($jsonResponse);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function tagsAction()
    {
        Json::factory($this->router->response())
            ->returnRawJson(
                SelectItemAdapter::factory(
                    $this->dic->get(TagService::class)
                        ->getAllBasic())->getJsonItemsFromModel());
    }

    /**
     * ItemsController constructor.
     *
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        $this->checks();
    }

    /**
     * Preparar los elementos para devolverlos
     *
     * @param array $items
     *
     * @return array
     */
    private function prepareItems(array $items)
    {
        $outItems = [];

        /** @var DataModelInterface $item */
        foreach ($items as $item) {
            $obj = new stdClass();
            $obj->id = $item->getId();
            $obj->name = $item->getName();

            $outItems[] = $obj;
        }

        return $outItems;
    }
}