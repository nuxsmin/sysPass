<?php
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

declare(strict_types=1);

namespace SP\Domain\Account\Dtos;

use SP\Domain\Common\Dtos\Dto;

/**
 * Class AccountHistoryViewDto
 */
final class AccountHistoryViewDto extends Dto
{

    public function __construct(
        public readonly ?int    $userId,
        public readonly ?int    $userGroupId,
        public readonly ?string $dateEdit,
        public readonly ?int    $accountId,
        public readonly ?int    $id,
        public readonly ?int    $passDateChange,
        public readonly ?int    $categoryId,
        public readonly ?int    $clientId,
        public readonly ?int    $passDate,
    ) {
    }
}
