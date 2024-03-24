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

namespace SP\Domain\Tag\Ports;

use SP\DataModel\ItemSearchData;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Tag\Models\Tag;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class TagService
 *
 * @package SP\Domain\Tag\Services
 */
interface TagService
{
    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): Tag;

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getByName(string $name): ?Tag;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function delete(int $id): TagService;

    /**
     * @param  int[]  $ids
     *
     * @throws SPException
     */
    public function deleteByIdBatch(array $ids): TagService;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     */
    public function create(Tag $itemData): int;

    /**
     * @throws SPException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function update(Tag $itemData): int;

    /**
     * Get all items from the service's repository
     *
     * @return Tag[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;
}
