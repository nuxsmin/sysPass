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

namespace SP\Tests\Stubs;

use Closure;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagRepository;

/**
 * In-memory TagRepository fake for use case unit tests.
 */
final class InMemoryTagRepository implements TagRepository
{
    /**
     * @var array<int, Tag>
     */
    private array $tags = [];

    private int $nextId = 1;

    public function transactionAware(Closure $closure, object $newThis): mixed
    {
        return $closure->call($newThis);
    }

    public function getAny(
        array $columns,
        string $from,
        ?string $where = null,
        ?array $bindValues = null
    ): QueryResult {
        return new QueryResult();
    }

    public function create(Tag $tag): QueryResult
    {
        $id = $this->nextId++;
        $stored = $tag->mutate(['id' => $id, 'hash' => sha1(strtolower($tag->getName() ?? ''))]);
        $this->tags[$id] = $stored;

        return new QueryResult(null, 1, $id);
    }

    public function update(Tag $tag): int
    {
        $id = (int)$tag->getId();

        if (!isset($this->tags[$id])) {
            return 0;
        }

        $this->tags[$id] = $tag->mutate(['hash' => sha1(strtolower($tag->getName() ?? ''))]);

        return 1;
    }

    public function getById(int $tagId): QueryResult
    {
        return isset($this->tags[$tagId])
            ? new QueryResult([$this->tags[$tagId]])
            : new QueryResult();
    }

    public function getByName(string $name): QueryResult
    {
        foreach ($this->tags as $tag) {
            if ($tag->getName() === $name) {
                return new QueryResult([$tag]);
            }
        }

        return new QueryResult();
    }

    public function deleteByIdBatch(array $ids): QueryResult
    {
        $deleted = 0;

        foreach ($ids as $id) {
            if (isset($this->tags[(int)$id])) {
                unset($this->tags[(int)$id]);
                $deleted++;
            }
        }

        return new QueryResult(null, $deleted);
    }

    public function delete(int $id): QueryResult
    {
        if (!isset($this->tags[$id])) {
            return new QueryResult();
        }

        unset($this->tags[$id]);

        return new QueryResult(null, 1);
    }

    public function getAll(): QueryResult
    {
        return new QueryResult(array_values($this->tags));
    }

    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        $tags = array_values($this->tags);

        return QueryResult::withTotalNumRows($tags, count($tags));
    }
}
