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

namespace SP\Tests\Generators;

use SP\Domain\Notification\Models\Notification;

/**
 * Class NotificationDataGenerator
 */
final class NotificationDataGenerator extends DataGenerator
{
    public function buildNotification(): Notification
    {
        return new Notification($this->notificationProperties());
    }

    private function notificationProperties(): array
    {
        return [
            'id' => $this->faker->randomNumber(3),
            'type' => $this->faker->colorName(),
            'component' => $this->faker->colorName(),
            'description' => $this->faker->text(),
            'date' => $this->faker->unixTime(),
            'checked' => $this->faker->boolean(),
            'sticky' => $this->faker->boolean(),
            'onlyAdmin' => $this->faker->boolean(),
            'userId' => $this->faker->randomNumber(3)
        ];
    }
}
