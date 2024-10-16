<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Account\Dtos;

use SP\Domain\Account\Models\AccountView;
use SP\Domain\Common\Dtos\Dto;
use SP\Domain\Common\Dtos\ItemDataTrait;
use SP\Domain\Common\Models\Item;

/**
 * Class AccountEnrichedDto
 */
class AccountEnrichedDto extends Dto
{
    use ItemDataTrait;

    private readonly int $id;
    /**
     * @var Item[] Los usuarios secundarios de la cuenta.
     */
    private array $users = [];
    /**
     * @var Item[] Los grupos secundarios de la cuenta.
     */
    private array $userGroups = [];
    /**
     * @var Item[] Las etiquetas de la cuenta.
     */
    private array $tags = [];

    /**
     * AccountDetailsResponse constructor.
     *
     * @param AccountView $accountView
     */
    public function __construct(private readonly AccountView $accountView)
    {
        $this->id = $accountView->getId();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param Item[] $users
     *
     * @return AccountEnrichedDto
     */
    public function withUsers(array $users): AccountEnrichedDto
    {
        $self = clone $this;
        $self->users = self::buildFromItemData($users);

        return $self;
    }

    /**
     * @param Item[] $groups
     *
     * @return AccountEnrichedDto
     */
    public function withUserGroups(array $groups): AccountEnrichedDto
    {
        $self = clone $this;
        $self->userGroups = self::buildFromItemData($groups);

        return $self;
    }

    /**
     * @param Item[] $tags
     *
     * @return AccountEnrichedDto
     */
    public function withTags(array $tags): AccountEnrichedDto
    {
        $self = clone $this;
        $self->tags = self::buildFromItemData($tags);

        return $self;
    }

    /**
     * @return Item[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @return Item[]
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @return Item[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getAccountView(): AccountView
    {
        return $this->accountView;
    }
}
