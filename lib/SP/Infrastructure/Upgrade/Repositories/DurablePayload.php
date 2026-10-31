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

namespace SP\Infrastructure\Upgrade\Repositories;

use Closure;
use LogicException;
use PDO;
use SP\Domain\Database\Ports\DatabaseInterface;
use SP\Domain\Database\Ports\QueryFactoryInterface;
use SP\Domain\Upgrade\Dtos\DurablePayloadRow;
use SP\Domain\Upgrade\Enums\DurablePayloadType;
use SP\Domain\Upgrade\Ports\DurablePayloadRepository;
use Throwable;

/**
 * Class DurablePayload
 */
final class DurablePayload implements DurablePayloadRepository
{
    public function __construct(
        private readonly DatabaseInterface     $database,
        private readonly QueryFactoryInterface $queryFactory
    ) {
    }

    public function findByType(DurablePayloadType $type): iterable
    {
        $query = $this->queryFactory->buildQuery($this->selectQuery($type));
        $result = $this->database->doFetchWithOptions(
            $this->queryFactory->buildQueryData($query),
            [],
            PDO::FETCH_ASSOC
        );

        $rows = [];

        foreach ($result as $data) {
            if (!is_array($data)) {
                continue;
            }

            $rows[] = new DurablePayloadRow(
                $type === DurablePayloadType::PluginData
                    ? sprintf('%s:%s', $data['name'], $data['itemId'])
                    : (string)$data['id'],
                isset($data['payload']) ? (string)$data['payload'] : null,
                isset($data['subtype']) ? (string)$data['subtype'] : null
            );
        }

        return $rows;
    }

    public function update(DurablePayloadType $type, string $id, string $payload): int
    {
        $query = $this->queryFactory->buildQuery(
            $this->updateQuery($type),
            ['payload' => $payload, 'id' => $id]
        );

        return $this->database->runQuery($this->queryFactory->buildQueryData($query))->getAffectedNumRows();
    }

    public function transactional(Closure $callback): mixed
    {
        $this->database->beginTransaction();

        try {
            $result = $callback();
            $this->database->endTransaction();

            return $result;
        } catch (Throwable $e) {
            $this->database->rollbackTransaction();

            throw $e;
        }
    }

    private function selectQuery(DurablePayloadType $type): string
    {
        return match ($type) {
            DurablePayloadType::AuthTokenVault => 'SELECT id, vault AS payload FROM AuthToken',
            DurablePayloadType::PublicLinkData => 'SELECT id, data AS payload FROM PublicLink',
            DurablePayloadType::UserProfile => 'SELECT id, profile AS payload FROM UserProfile',
            DurablePayloadType::UserPreferences => 'SELECT id, preferences AS payload FROM User',
            DurablePayloadType::ItemPreset => 'SELECT id, data AS payload, type AS subtype FROM ItemPreset',
            DurablePayloadType::PluginData => 'SELECT name, itemId, data AS payload FROM PluginData'
        };
    }

    private function updateQuery(DurablePayloadType $type): string
    {
        return match ($type) {
            DurablePayloadType::AuthTokenVault => 'UPDATE AuthToken SET vault = :payload WHERE id = :id LIMIT 1',
            DurablePayloadType::PublicLinkData => 'UPDATE PublicLink SET data = :payload WHERE id = :id LIMIT 1',
            DurablePayloadType::UserProfile => 'UPDATE UserProfile SET profile = :payload WHERE id = :id LIMIT 1',
            DurablePayloadType::UserPreferences => 'UPDATE User SET preferences = :payload WHERE id = :id LIMIT 1',
            DurablePayloadType::ItemPreset => 'UPDATE ItemPreset SET data = :payload WHERE id = :id LIMIT 1',
            DurablePayloadType::PluginData => throw new LogicException('Plugin data payloads are not rewritable')
        };
    }
}
