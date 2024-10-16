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

namespace SP\Domain\CustomField\Services;

use JsonSerializable;
use SP\Domain\Common\Dtos\Dto;

/**
 * Class CustomFieldItem
 */
final class CustomFieldItem extends Dto implements JsonSerializable
{

    public function __construct(
        public readonly bool   $required,
        public readonly bool   $showInList,
        public readonly string $help,
        public readonly int    $definitionId,
        public readonly string $definitionName,
        public readonly int    $typeId,
        public readonly string $typeName,
        public readonly string $typeText,
        public readonly int    $moduleId,
        public readonly string $formId,
        public readonly mixed  $value,
        public readonly bool   $isEncrypted,
        public readonly bool   $isValueEncrypted
    ) {
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'required' => $this->required,
            'showInList' => $this->showInList,
            'help' => $this->help,
            'typeId' => $this->typeId,
            'typeName' => $this->typeName,
            'typeText' => $this->typeText,
            'moduleId' => $this->moduleId,
            'value' => $this->value,
            'isEncrypted' => $this->isEncrypted,
            'isValueEncrypted' => $this->isValueEncrypted,
        ];
    }
}
