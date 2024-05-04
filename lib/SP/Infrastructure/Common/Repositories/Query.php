<?php
declare(strict_types=1);
/**
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

namespace SP\Infrastructure\Common\Repositories;

use Aura\SqlQuery\Common\Quoter;
use Aura\SqlQuery\QueryInterface;

/**
 * Class Query
 */
final class Query implements QueryInterface
{
    private Quoter $quoter;

    private function __construct(private readonly string $query, private array $values)
    {
        $this->quoter = new Quoter();
    }

    /**
     * Build an instance of this class for the given database
     *
     * @param string $query
     * @param array $values
     *
     * @return Query
     */
    public static function buildForMySQL(string $query, array $values): Query
    {
        return new Query($query, $values);
    }

    /**
     *
     * Builds this query object into a string.
     *
     * @return string
     *
     */
    public function __toString()
    {
        return $this->query;
    }

    /**
     *
     * Returns the prefix to use when quoting identifier names.
     *
     * @return string
     *
     */
    public function getQuoteNamePrefix(): string
    {
        return $this->quoter->getQuoteNamePrefix();
    }

    /**
     *
     * Returns the suffix to use when quoting identifier names.
     *
     * @return string
     *
     */
    public function getQuoteNameSuffix(): string
    {
        return $this->quoter->getQuoteNameSuffix();
    }

    /**
     *
     * Adds values to bind into the query; merges with existing values.
     *
     * @param array $bind_values Values to bind to the query.
     *
     * @return $this
     *
     */
    public function bindValues(array $bind_values): self
    {
        // array_merge() renumbers integer keys, which is bad for
        // question-mark placeholders
        foreach ($bind_values as $key => $val) {
            $this->bindValue($key, $val);
        }

        return $this;
    }

    /**
     *
     * Binds a single value to the query.
     *
     * @param string $name The placeholder name or number.
     * @param mixed $value The value to bind to the placeholder.
     *
     * @return $this
     *
     */
    public function bindValue($name, $value): self
    {
        $this->values[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getBindValues(): array
    {
        return $this->values;
    }

    public function getStatement(): string
    {
        return $this->query;
    }

    public function resetFlags()
    {
        // TODO: Implement resetFlags() method.
    }
}
