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

namespace SP\Domain\CustomField\Adapters;

use SP\Domain\Common\Adapters\Adapter;
use SP\Domain\Common\Dtos\Dto;
use SP\Domain\CustomField\Ports\CustomFieldAdapter;
use SP\Domain\CustomField\Services\CustomFieldItem;

/**
 * Class CustomFieldAdapter
 */
final class CustomField extends Adapter implements CustomFieldAdapter
{
    public function transform(Dto|CustomFieldItem $data): array
    {
        return [
            'type' => $data->typeName,
            'typeText' => $data->typeText,
            'definitionId' => $data->definitionId,
            'definitionName' => $data->definitionName,
            'help' => $data->help,
            'value' => $data->value,
            'encrypted' => $data->isEncrypted,
            'required' => $data->required,
        ];
    }
}
