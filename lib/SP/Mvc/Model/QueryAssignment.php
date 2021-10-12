<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mvc\Model;

/**
 * Class QueryAssignment
 *
 * @package SP\Mvc\Model
 */
final class QueryAssignment
{
    protected array $fields = [];
    protected array $values = [];

    /**
     * @param string $field
     * @param        $value
     *
     * @return $this
     */
    public function addField(string $field, $value): QueryAssignment
    {
        if (strpos($field, '=') === false) {
            $this->fields[] = $field . ' = ?';
            $this->values[] = $value;
        }

        return $this;
    }

    public function setFields(array $fields, array $values): QueryAssignment
    {
        $this->fields = array_map(
            static function ($value) {
                return strpos($value, '=') === false
                    ? "$value = ?"
                    : $value;
            },
            $fields
        );

        $this->values = array_merge($this->values, $values);

        return $this;
    }

    public function getAssignments(): ?string
    {
        return $this->hasFields()
            ? implode(',', $this->fields)
            : null;
    }

    public function hasFields(): bool
    {
        return count($this->fields) > 0;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}