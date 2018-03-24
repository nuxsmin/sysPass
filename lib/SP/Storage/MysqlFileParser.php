<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Storage;

/**
 * Class MysqlFileParser
 * @package SP\Storage
 */
class MysqlFileParser implements DatabaseFileInterface
{

    /**
     * Parses a database script file and returns an array of lines parsed
     *
     * @param FileHandler $fileHandler
     * @param string $delimiter
     * @return array
     * @throws FileException
     */
    public function parse(FileHandler $fileHandler, $delimiter = ';')
    {
        $queries = [];
        $query = '';
        $delimiterLength = strlen($delimiter);

        $handle = $fileHandler->open('rb');

        while (($buffer = fgets($handle)) !== false) {
            $buffer = trim($buffer);
            $length = strlen($buffer);

            if ($length > 0
                && strpos($buffer, '--') !== 0
            ) {
                // Checks if line is a set wrapped by a comment
                $setComment = preg_match(/** @lang RegExp */
                    '#^(?P<statement>/\*!\d+.*\*/)#', $buffer, $matches);

                if ($setComment) {
                    $queries[] = $matches['statement'];
                } else {
                    $end = strrpos($buffer, $delimiter) === $length - $delimiterLength;

                    if (!$end) {
                        $query .= $buffer . PHP_EOL;
                    } elseif ($end
                        && empty($query)
                        && strpos($buffer, 'DELIMITER') === false
                    ) {
                        $queries[] = trim(substr_replace($buffer, '', $length - $delimiterLength), $delimiterLength);
                    } elseif (!empty($query)) { // End of query
                        $queries[] = trim($query);

                        $query = '';
                    }
                }
            }
        }

        $fileHandler->close();

        return $queries;
    }
}