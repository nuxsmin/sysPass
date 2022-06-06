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

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\DataModel\DataModelInterface;
use SP\DataModel\NotificationData;
use SP\Domain\Account\Services\AccountService;
use SP\Domain\Category\Services\CategoryService;
use SP\Domain\Client\Services\ClientService;
use SP\Domain\Notification\Services\NotificationService;
use SP\Domain\Tag\Services\TagService;
use SP\Html\Html;
use SP\Http\Json;
use SP\Http\JsonResponse;
use SP\Mvc\View\Components\SelectItemAdapter;
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
     * @param int|null $accountId
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function accountsUserAction(?int $accountId = null): void
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

        Json::fromDic()->returnJson($jsonResponse);
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function clientsAction(): void
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
    public function categoriesAction(): void
    {
        Json::factory($this->router->response())
            ->returnRawJson(
                SelectItemAdapter::factory(
                    $this->dic->get(CategoryService::class)
                        ->getAllBasic())->getJsonItemsFromModel());
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function notificationsAction(): void
    {
        $notifications = array_map(
            static function ($notification) {
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
    public function tagsAction(): void
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
    protected function initialize(): void
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
    private function prepareItems(array $items): array
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