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

namespace SPT\Domain\Account\Search;

use Faker\Factory;
use SP\Domain\Account\Search\AccountSearchConstants;

/**
 * Trait AccountSearchTokenizerDataTrait
 */
trait AccountSearchTokenizerDataTrait
{
    public static function searchByItemDataProvider(): array
    {
        $faker = Factory::create();
        $id = $faker->numberBetween(1);
        $name = $faker->userName;
        $file = sprintf('%s.%s', $faker->name(), $faker->fileExtension);

        $conditions = [
            sprintf('id:%d', $id),
            sprintf('user:"%s"', $name),
            sprintf('group:"%s"', $name),
            sprintf('file:"%s"', $file),
            sprintf('owner:"%s"', $name),
            sprintf('maingroup:"%s"', $name),
            sprintf('client:"%s"', $name),
            sprintf('category:"%s"', $name),
            sprintf('name_regex:"^%s$"', $name),
        ];

        return [
            [$conditions[0], [AccountSearchConstants::FILTER_ACCOUNT_ID => $id]],
            [$conditions[1], [AccountSearchConstants::FILTER_USER_NAME => $name]],
            [$conditions[2], [AccountSearchConstants::FILTER_GROUP_NAME => $name]],
            [$conditions[3], [AccountSearchConstants::FILTER_FILE_NAME => $file]],
            [$conditions[4], [AccountSearchConstants::FILTER_OWNER => $name]],
            [$conditions[5], [AccountSearchConstants::FILTER_MAIN_GROUP => $name]],
            [$conditions[6], [AccountSearchConstants::FILTER_CLIENT_NAME => $name]],
            [$conditions[7], [AccountSearchConstants::FILTER_CATEGORY_NAME => $name]],
            [$conditions[8], [AccountSearchConstants::FILTER_ACCOUNT_NAME_REGEX => sprintf('^%s$', $name)],],
            [
                implode(' ', $conditions),
                [
                    AccountSearchConstants::FILTER_ACCOUNT_ID         => $id,
                    AccountSearchConstants::FILTER_USER_NAME          => $name,
                    AccountSearchConstants::FILTER_GROUP_NAME         => $name,
                    AccountSearchConstants::FILTER_FILE_NAME          => $file,
                    AccountSearchConstants::FILTER_OWNER              => $name,
                    AccountSearchConstants::FILTER_MAIN_GROUP         => $name,
                    AccountSearchConstants::FILTER_CLIENT_NAME        => $name,
                    AccountSearchConstants::FILTER_CATEGORY_NAME      => $name,
                    AccountSearchConstants::FILTER_ACCOUNT_NAME_REGEX => sprintf('^%s$', $name),
                ],
            ],
        ];
    }

    public static function searchByConditionDataProvider(): array
    {
        $conditions = [
            'is:expired',
            'not:expired',
            'is:private',
            'not:private',
        ];

        return [
            ...array_map(static fn($value) => [$value, [$value]], $conditions),
            [implode(' ', $conditions), array_slice($conditions, -2)],
        ];
    }

    public static function searchUsingOperatorDataProvider(): array
    {
        $conditions = [
            'test string' => null,
            'op:and'      => 'and',
            'op:or'       => 'or',
        ];

        return [
            ...array_map(static fn($key, $value) => [$key, $value], array_keys($conditions), array_values($conditions)),
            [implode(' ', array_keys($conditions)), array_pop($conditions)],
        ];
    }

    public static function searchUsingStringDataProvider(): array
    {
        $faker = Factory::create();

        $conditions = [
            $faker->address,
            $faker->streetAddress,
            $faker->name,
            $faker->userName,
            $faker->catchPhrase,
            $faker->ipv4,
            $faker->bankAccountNumber,
            $faker->companyEmail,
            $faker->domainName,
        ];

        return [
            ...array_map(static fn($value) => [$value, $value], $conditions),
        ];
    }
}
