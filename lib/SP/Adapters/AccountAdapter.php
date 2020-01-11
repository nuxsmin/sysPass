<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2020, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Adapters;

use League\Fractal\TransformerAbstract;
use SP\Config\ConfigData;
use SP\Core\Acl\ActionsInterface;
use SP\DataModel\Dto\AccountDetailsResponse;
use SP\DataModel\ItemData;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\CustomField\CustomFieldItem;
use SP\Util\Link;

/**
 * Class AccountAdapter
 *
 * @package SP\Adapters
 */
final class AccountAdapter extends TransformerAbstract
{
    /**
     * @var CustomFieldItem[]
     */
    private $customFields;
    /**
     * @var ConfigData
     */
    private $configData;

    /**
     * AccountAdapter constructor.
     *
     * @param ConfigData $configData
     */
    public function __construct(ConfigData $configData)
    {
        $this->configData = $configData;
    }

    /**
     * @param ItemData[] $items
     */
    public function withCustomFields(array $items)
    {
        $this->customFields = $items;
    }

    /**
     * @param AccountDetailsResponse $data
     *
     * @return array
     */
    public function transform(AccountDetailsResponse $data)
    {
        $account = $data->getAccountVData();

        return [
            'id' => (int)$account->getId(),
            'name' => $account->getName(),
            'clientId' => (int)$account->getClientId(),
            'clientName' => $account->getClientName(),
            'categoryId' => (int)$account->getCategoryId(),
            'categoryName' => $account->getCategoryName(),
            'userId' => (int)$account->getUserId(),
            'userName' => $account->getUserName(),
            'userLogin' => $account->getUserLogin(),
            'userGroupId' => (int)$account->getUserGroupId(),
            'userGroupName' => $account->getUserGroupName(),
            'userEditId' => (int)$account->getUserEditId(),
            'userEditName' => $account->getUserEditName(),
            'userEditLogin' => $account->getUserEditLogin(),
            'login' => $account->getLogin(),
            'url' => $account->getUrl(),
            'notes' => $account->getNotes(),
            'otherUserEdit' => (int)$account->getOtherUserEdit(),
            'otherUserGroupEdit' => (int)$account->getOtherUserGroupEdit(),
            'dateAdd' => (int)$account->getDateAdd(),
            'dateEdit' => (int)$account->getDateEdit(),
            'countView' => (int)$account->getCountView(),
            'countDecrypt' => (int)$account->getCountDecrypt(),
            'isPrivate' => (int)$account->getIsPrivate(),
            'isPrivateGroup' => (int)$account->getIsPrivateGroup(),
            'passDate' => (int)$account->getPassDate(),
            'passDateChange' => (int)$account->getPassDateChange(),
            'parentId' => (int)$account->getParentId(),
            'publicLinkHash' => $account->getPublicLinkHash(),
            'tags' => SelectItemAdapter::factory($data->getTags())->getItemsFromModel(),
            'users' => SelectItemAdapter::factory($data->getUsers())->getItemsFromModel(),
            'userGroups' => SelectItemAdapter::factory($data->getUserGroups())->getItemsFromModel(),
            'customFields' => $this->customFields,
            'links' => [
                [
                    'rel' => 'self',
                    'uri' => Link::getDeepLink($account->getId(),
                        ActionsInterface::ACCOUNT_VIEW,
                        $this->configData,
                        true)
                ]
            ],
        ];
    }


}