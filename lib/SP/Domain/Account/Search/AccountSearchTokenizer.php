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

namespace SP\Domain\Account\Search;

use SP\Util\Filter;

/**
 * Class AccountSearchTokenizer
 */
final class AccountSearchTokenizer
{
    private const SEARCH_REGEX = /** @lang RegExp */
        '/(?<search>(?<!:)\b[^:]+\b(?!:))|(?<filter_subject>[a-zа-я_]+):(?<filter_condition>[^"\'\s]+|"[^":]+")/u';

    private const FILTERS = [
        'condition' => [
            'subject'   => ['is', 'not'],
            'condition' => ['expired', 'private'],
        ],
        'items'     => [
            'subject'   => [
                'id'         => AccountSearchConstants::FILTER_ACCOUNT_ID,
                'user'       => AccountSearchConstants::FILTER_USER_NAME,
                'group'      => AccountSearchConstants::FILTER_GROUP_NAME,
                'file'       => AccountSearchConstants::FILTER_FILE_NAME,
                'owner'      => AccountSearchConstants::FILTER_OWNER,
                'maingroup'  => AccountSearchConstants::FILTER_MAIN_GROUP,
                'client'     => AccountSearchConstants::FILTER_CLIENT_NAME,
                'category'   => AccountSearchConstants::FILTER_CATEGORY_NAME,
                'name_regex' => AccountSearchConstants::FILTER_ACCOUNT_NAME_REGEX,
            ],
            'condition' => null,
        ],
        'operator'  => [
            'subject'   => ['op'],
            'condition' => [AccountSearchConstants::FILTER_CHAIN_AND, AccountSearchConstants::FILTER_CHAIN_OR],
        ],
    ];

    /**
     * @param  string  $search
     *
     * @return AccountSearchTokens|null
     */
    public function tokenizeFrom(string $search): ?AccountSearchTokens
    {
        $match = preg_match_all(self::SEARCH_REGEX, $search, $filters);

        if (empty($match)) {
            return null;
        }

        $filtersAndValues = array_filter(
            array_combine(
                $filters['filter_subject'],
                array_map(static fn($value) => str_replace('"', '', $value), $filters['filter_condition'])
            )
        );

        return new AccountSearchTokens(
            Filter::safeSearchString(trim($filters['search'][0] ?? '')),
            $this->getConditions($filtersAndValues),
            $this->getItems($filtersAndValues),
            $this->getOperator($filtersAndValues),
        );
    }

    /**
     * @param  array  $filters
     *
     * @return array
     */
    private function getConditions(array $filters): array
    {
        return array_filter(
            array_map(
                static function ($subject, $condition) {
                    if (in_array($subject, self::FILTERS['condition']['subject'], true)
                        && in_array($condition, self::FILTERS['condition']['condition'], true)
                    ) {
                        return sprintf("%s:%s", $subject, $condition);
                    }

                    return null;
                },
                array_keys($filters),
                array_values($filters)
            )
        );
    }

    /**
     * @param  array  $filtersAndValues
     *
     * @return array
     */
    private function getItems(array $filtersAndValues): array
    {
        $items = array_filter(
            $filtersAndValues,
            static function ($value, $key) {
                return array_key_exists($key, self::FILTERS['items']['subject']) && !empty($value);
            },
            ARRAY_FILTER_USE_BOTH
        );

        return array_combine(
            array_map(static fn($key) => self::FILTERS['items']['subject'][$key], array_keys($items)),
            array_values($items)
        );
    }

    /**
     * @param  array  $filtersAndValues
     *
     * @return string|null
     */
    private function getOperator(array $filtersAndValues): ?string
    {
        $operator = array_filter(
            $filtersAndValues,
            static function ($value, $key) {
                return in_array($key, self::FILTERS['operator']['subject'], true)
                       && in_array($value, self::FILTERS['operator']['condition'], true);
            },
            ARRAY_FILTER_USE_BOTH
        );

        return array_shift($operator);
    }
}
