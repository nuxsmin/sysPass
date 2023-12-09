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

namespace SPT\Generators;

use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;

/**
 * Class PublicLinkDataGenerator
 */
final class PublicLinkDataGenerator extends DataGenerator
{
    public function buildPublicLink(): PublicLinkData
    {
        return new PublicLinkData($this->getPublicLinkProperties());
    }

    private function getPublicLinkProperties(): array
    {
        return [
            'id'              => $this->faker->randomNumber(),
            'itemId'          => $this->faker->randomNumber(),
            'hash'            => $this->faker->randomNumber(),
            'userId'          => $this->faker->randomNumber(),
            'typeId'          => $this->faker->randomNumber(),
            'notify'          => $this->faker->boolean,
            'dateAdd'         => $this->faker->unixTime(),
            'dateUpdate'      => $this->faker->unixTime(),
            'dateExpire'      => $this->faker->unixTime(),
            'countViews'      => $this->faker->randomNumber(),
            'totalCountViews' => $this->faker->randomNumber(),
            'maxCountViews'   => $this->faker->randomNumber(),
            'useInfo'         => serialize($this->getUseInfo()),
            'data'            => $this->faker->text,
        ];
    }

    private function getUseInfo(): array
    {
        return array_map(
            fn() => [
                'who'   => $this->faker->ipv4,
                'time'  => $this->faker->unixTime,
                'hash'  => $this->faker->sha1,
                'agent' => $this->faker->userAgent,
                'https' => $this->faker->boolean,
            ],
            range(0, 9)
        );
    }

    public function buildPublicLinkList(): PublicLinkListData
    {
        return new PublicLinkListData(
            array_merge($this->getPublicLinkProperties(), [
                'userName'    => $this->faker->name,
                'userLogin'   => $this->faker->userName,
                'accountName' => $this->faker->colorName,
                'clientName'  => $this->faker->company,
            ])
        );
    }
}
