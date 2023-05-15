<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Infrastructure\Database;

use Aura\SqlQuery\Common\Select;
use Aura\SqlQuery\QueryInterface;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Common\Models\Simple;
use function SP\__u;

/**
 * Class QueryData
 *
 * @package SP\Storage
 */
final class QueryData
{
    private const DEFAULT_MAP_CLASS = Simple::class;
    protected string  $mapClassName   = self::DEFAULT_MAP_CLASS;
    protected ?string $onErrorMessage = null;

    public function __construct(private QueryInterface $query) {}

    public static function build(QueryInterface $query): QueryData
    {
        return new self($query);
    }

    public static function buildWithMapper(QueryInterface $query, string $class): QueryData
    {
        $self = new self($query);
        $self->mapClassName = self::checkClassOrDefault($class);

        return $self;
    }

    private static function checkClassOrDefault(string $class): string
    {
        return class_exists($class) ? $class : self::DEFAULT_MAP_CLASS;
    }

    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    public function getMapClassName(): string
    {
        return $this->mapClassName;
    }

    public function setMapClassName(string $class): QueryData
    {
        $this->mapClassName = self::checkClassOrDefault($class);

        return $this;
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getQueryCount(): QueryInterface
    {
        if ($this->query instanceof Select) {
            $countQuery = (clone $this->query)
                ->resetCols()
                ->resetOrderBy()
                ->resetGroupBy()
                ->resetHaving()
                ->page(0);

            $countQuery->cols(['COUNT(*)']);

            return $countQuery;
        }

        throw new QueryException(__u('Invalid query type for count'));
    }

    public function getOnErrorMessage(): string
    {
        return $this->onErrorMessage ?: __u('Error while querying');
    }

    public function setOnErrorMessage(string $onErrorMessage): QueryData
    {
        $this->onErrorMessage = $onErrorMessage;

        return $this;
    }
}
