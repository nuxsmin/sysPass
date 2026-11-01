<?php

declare(strict_types=1);
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

namespace SP\Tests\Stubs;

use Closure;
use SP\Domain\Upgrade\Dtos\DurablePayloadRow;
use SP\Domain\Upgrade\Enums\DurablePayloadType;
use SP\Domain\Upgrade\Ports\DurablePayloadRepository;
use Throwable;

/**
 * Class InMemoryDurablePayloadRepository
 */
final class InMemoryDurablePayloadRepository implements DurablePayloadRepository
{
    /**
     * @var array<string, list<DurablePayloadRow>>
     */
    private array $rows = [];

    /**
     * @var array<string, string>
     */
    public array $updates = [];

    public int $transactionCount = 0;

    public ?Throwable $updateException = null;

    public ?Throwable $findException = null;

    public bool $returnZeroAffected = false;

    public function add(DurablePayloadType $type, DurablePayloadRow $row): void
    {
        $this->rows[$type->value][] = $row;
    }

    public function findByType(DurablePayloadType $type): iterable
    {
        if ($this->findException !== null) {
            throw $this->findException;
        }

        return $this->rows[$type->value] ?? [];
    }

    public function update(DurablePayloadType $type, string $id, string $payload): int
    {
        if ($this->updateException !== null) {
            throw $this->updateException;
        }

        if ($this->returnZeroAffected) {
            return 0;
        }

        foreach ($this->rows[$type->value] ?? [] as $index => $row) {
            if ($row->id === $id) {
                $this->rows[$type->value][$index] = new DurablePayloadRow($id, $payload, $row->subtype);
                $this->updates[$type->value . ':' . $id] = $payload;

                return 1;
            }
        }

        return 0;
    }

    public function transactional(Closure $callback): mixed
    {
        $this->transactionCount++;

        return $callback();
    }

    public function payload(DurablePayloadType $type, string $id): ?string
    {
        foreach ($this->rows[$type->value] ?? [] as $row) {
            if ($row->id === $id) {
                return $row->payload;
            }
        }

        return null;
    }
}
