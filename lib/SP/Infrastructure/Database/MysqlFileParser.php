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

namespace SP\Infrastructure\Database;

use SP\Domain\Database\Ports\DatabaseFileInterface;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Infrastructure\File\FileException;

/**
 * Class MysqlFileParser
 */
final readonly class MysqlFileParser implements DatabaseFileInterface
{
    public function __construct(private FileHandlerInterface $fileHandler)
    {
    }

    /**
     * Parses a database script file and yields the queries parsed
     *
     * @throws FileException
     */
    public function parse(string $delimiter = ';'): iterable
    {
        $this->fileHandler->checkIsReadable();

        $query = [];
        $delimiterLength = strlen($delimiter);

        foreach ($this->fileHandler->read() as $data) {
            $line = trim($data);
            $lineLength = strlen($line);

            if ($lineLength > 0 && !(str_starts_with($line, '--') || str_starts_with($line, 'DELIMITER'))) {
                if (substr($line, -$delimiterLength) === $delimiter) {
                    $query[] = substr($line, 0, $lineLength - $delimiterLength);

                    yield implode(' ', $query);

                    $query = [];
                } else {
                    $query[] = $line;
                }
            }
        }
    }
}
