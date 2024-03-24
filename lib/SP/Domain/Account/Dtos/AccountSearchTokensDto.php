<?php
/*
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

/**
 * Class AccountSearchTokens
 */
final class AccountSearchTokensDto
{
    private string  $search;
    private array   $conditions;
    private array   $items;
    private ?string $operator;

    /**
     * @param string $search
     * @param array $conditions
     * @param array $items
     * @param string|null $operator
     */
    public function __construct(string $search, array $conditions, array $items, ?string $operator)
    {
        $this->search = $search;
        $this->conditions = $conditions;
        $this->items = $items;
        $this->operator = $operator;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getOperator(): ?string
    {
        return $this->operator;
    }

    public function getSearch(): string
    {
        return $this->search;
    }
}
