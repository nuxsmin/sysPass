<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Core\Messages;

/**
 * Class TextFormatter
 *
 * @package SP\Core\Messages
 */
final class TextFormatter implements FormatterInterface
{
    /**
     * @var string
     */
    private $delimiter;

    /**
     * TextFormatter constructor.
     *
     * @param string $delimiter
     */
    public function __construct($delimiter = PHP_EOL)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @param array $text
     * @param bool  $translate
     *
     * @return string
     */
    public function formatDetail(array $text, bool $translate = false): string
    {
        return implode(
            $this->delimiter,
            array_map(function ($value) use ($translate) {
                return sprintf(
                    '%s: %s',
                    $translate ? __($value[0]) : $value[0]
                    , $translate ? __($value[1]) : $value[1]
                );
            }, $text));

    }

    /**
     * @param array $text
     * @param bool  $translate
     *
     * @return string
     */
    public function formatDescription(array $text, bool $translate = false): string
    {
        if ($translate === true) {
            return implode($this->delimiter, array_map('__', $text));
        }

        return implode($this->delimiter, $text);
    }
}