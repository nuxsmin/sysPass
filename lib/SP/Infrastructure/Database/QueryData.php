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

namespace SP\Infrastructure\Database;

use Aura\SqlQuery\Common\Select;
use Aura\SqlQuery\QueryInterface;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Common\Out\SimpleModel;
use function SP\__u;

/**
 * Class QueryData
 *
 * @package SP\Storage
 */
final class QueryData
{
    protected array          $params         = [];
    protected QueryInterface $query;
    protected ?string        $mapClassName   = SimpleModel::class;
    protected bool           $useKeyPair     = false;
    protected ?string        $select         = null;
    protected ?string        $from           = null;
    protected ?string        $where          = null;
    protected ?string        $groupBy        = null;
    protected ?string        $order          = null;
    protected ?string        $limit          = null;
    protected ?string        $onErrorMessage = null;

    public function __construct(QueryInterface $query)
    {
        $this->query = $query;
    }

    public static function build(QueryInterface $query): QueryData
    {
        return new self($query);
    }

    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    public function getMapClassName(): ?string
    {
        return $this->mapClassName;
    }

    public function setMapClassName(string $mapClassName): QueryData
    {
        $this->mapClassName = $mapClassName;

        return $this;
    }

    public function isUseKeyPair(): bool
    {
        return $this->useKeyPair;
    }

    public function addParams(array $params): void
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function getQueryCount(): QueryInterface
    {
        if ($this->query instanceof Select) {
            $countQuery = (clone $this->query)
                ->resetFlags()
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
