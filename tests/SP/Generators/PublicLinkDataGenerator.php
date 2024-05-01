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

use SP\Domain\Account\Models\PublicLink;
use SP\Domain\Account\Models\PublicLinkList;

/**
 * Class PublicLinkDataGenerator
 */
final class PublicLinkDataGenerator extends DataGenerator
{
    public function buildPublicLink(): PublicLink
    {
        return new PublicLink($this->getPublicLinkProperties());
    }

    private function getPublicLinkProperties(): array
    {
        return [
            'id' => $this->faker->randomNumber(3),
            'itemId' => $this->faker->randomNumber(3),
            'hash' => $this->faker->randomNumber(3),
            'userId' => $this->faker->randomNumber(3),
            'typeId' => $this->faker->randomNumber(3),
            'notify' => $this->faker->boolean(),
            'dateAdd'         => $this->faker->unixTime(),
            'dateUpdate'      => $this->faker->unixTime(),
            'dateExpire'      => $this->faker->unixTime(),
            'countViews' => $this->faker->randomNumber(3),
            'totalCountViews' => $this->faker->randomNumber(3),
            'maxCountViews' => $this->faker->randomNumber(3),
            'useInfo'         => serialize($this->getUseInfo()),
            'data' => $this->faker->text(),
        ];
    }

    private function getUseInfo(): array
    {
        return array_map(
            fn() => [
                'who' => $this->faker->ipv4(),
                'time' => $this->faker->unixTime(),
                'hash' => $this->faker->sha1(),
                'agent' => $this->faker->userAgent(),
                'https' => $this->faker->boolean(),
            ],
            range(0, 9)
        );
    }

    public function buildPublicLinkList(): PublicLinkList
    {
        return new PublicLinkList(
            array_merge($this->getPublicLinkProperties(), [
                'userName' => $this->faker->name(),
                'userLogin' => $this->faker->userName(),
                'accountName' => $this->faker->colorName(),
                'clientName' => $this->faker->company(),
            ])
        );
    }
}
