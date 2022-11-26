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

namespace SP\Tests\Generators;

use SP\DataModel\AccountVData;
use SP\DataModel\Dto\AccountEnrichedDto;
use SP\DataModel\ItemData;

/**
 * Class AccountDataGenerator
 */
final class AccountDataGenerator extends DataGenerator
{
    public function getAccountData(): AccountEnrichedDto
    {
        $accountData = new AccountVData([
            'id'                 => $this->faker->randomNumber(),
            'name'               => $this->faker->name,
            'clientId'           => $this->faker->randomNumber(),
            'clientName'         => $this->faker->name,
            'categoryId'         => $this->faker->randomNumber(),
            'categoryName'       => $this->faker->name,
            'userId'             => $this->faker->randomNumber(),
            'userName'           => $this->faker->userName,
            'userLogin'          => $this->faker->name,
            'userGroupId'        => $this->faker->randomNumber(),
            'userGroupName'      => $this->faker->name,
            'userEditId'         => $this->faker->randomNumber(),
            'userEditName'       => $this->faker->userName,
            'userEditLogin'      => $this->faker->name,
            'login'              => $this->faker->name,
            'url'                => $this->faker->url,
            'notes'              => $this->faker->text,
            'otherUserEdit'      => $this->faker->boolean,
            'otherUserGroupEdit' => $this->faker->boolean,
            'dateAdd'            => $this->faker->unixTime,
            'dateEdit'           => $this->faker->unixTime,
            'countView'          => $this->faker->randomNumber(),
            'countDecrypt'       => $this->faker->randomNumber(),
            'isPrivate'          => $this->faker->boolean,
            'isPrivateGroup'     => $this->faker->boolean,
            'passDate'           => $this->faker->unixTime,
            'passDateChange'     => $this->faker->unixTime,
            'parentId'           => $this->faker->randomNumber(),
            'publicLinkHash'     => $this->faker->sha1,
        ]);
        $out = new AccountEnrichedDto($accountData);
        $out->setUsers($this->buildItemData());
        $out->setTags($this->buildItemData());
        $out->setUserGroups($this->buildItemData());

        return $out;
    }

    /**
     * @return ItemData[]
     */
    public function buildItemData(): array
    {
        return array_map(
            fn() => new ItemData(['id' => $this->faker->randomNumber(), 'name' => $this->faker->name]),
            range(0, 9)
        );
    }
}
