<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Account\Adapters;

use League\Fractal\Resource\Collection;
use SP\Core\Acl\ActionsInterface;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Common\Adapters\Adapter;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\CustomField\Adapters\CustomFieldAdapter;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Util\Link;

/**
 * Class AccountAdapter
 */
final class AccountAdapter extends Adapter implements AccountAdapterInterface
{
    use ItemTrait;

    protected array $availableIncludes = ['customFields'];

    private CustomFieldServiceInterface $customFieldService;

    public function __construct(ConfigDataInterface $configData, CustomFieldServiceInterface $customFieldService)
    {
        parent::__construct($configData);

        $this->customFieldService = $customFieldService;
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function includeCustomFields(AccountEnrichedDto $data,): Collection
    {
        return $this->collection(
            $this->getCustomFieldsForItem(ActionsInterface::ACCOUNT, $data->getId(), $this->customFieldService),
            new CustomFieldAdapter($this->configData)
        );
    }

    public function transform(AccountEnrichedDto $data): array
    {
        $account = $data->getAccountVData();

        return [
            'id'                 => (int)$account->getId(),
            'name'               => $account->getName(),
            'clientId'           => $account->getClientId(),
            'clientName'         => $account->getClientName(),
            'categoryId'         => $account->getCategoryId(),
            'categoryName'       => $account->getCategoryName(),
            'userId'             => $account->getUserId(),
            'userName'           => $account->getUserName(),
            'userLogin'          => $account->getUserLogin(),
            'userGroupId'        => $account->getUserGroupId(),
            'userGroupName'      => $account->getUserGroupName(),
            'userEditId'         => $account->getUserEditId(),
            'userEditName'       => $account->getUserEditName(),
            'userEditLogin'      => $account->getUserEditLogin(),
            'login'              => $account->getLogin(),
            'url'                => $account->getUrl(),
            'notes'              => $account->getNotes(),
            'otherUserEdit'      => $account->getOtherUserEdit(),
            'otherUserGroupEdit' => $account->getOtherUserGroupEdit(),
            'dateAdd'            => $account->getDateAdd(),
            'dateEdit'           => $account->getDateEdit(),
            'countView'          => $account->getCountView(),
            'countDecrypt'       => $account->getCountDecrypt(),
            'isPrivate'          => $account->getIsPrivate(),
            'isPrivateGroup'     => $account->getIsPrivateGroup(),
            'passDate'           => $account->getPassDate(),
            'passDateChange'     => $account->getPassDateChange(),
            'parentId'           => $account->getParentId(),
            'publicLinkHash'     => $account->getPublicLinkHash(),
            'tags'               => SelectItemAdapter::factory($data->getTags())->getItemsFromModel(),
            'users'              => SelectItemAdapter::factory($data->getUsers())->getItemsFromModel(),
            'userGroups'         => SelectItemAdapter::factory($data->getUserGroups())->getItemsFromModel(),
            'customFields'       => null,
            'links'              => [
                [
                    'rel' => 'self',
                    'uri' => Link::getDeepLink(
                        $account->getId(),
                        ActionsInterface::ACCOUNT_VIEW,
                        $this->configData,
                        true
                    ),
                ],
            ],
        ];
    }
}
