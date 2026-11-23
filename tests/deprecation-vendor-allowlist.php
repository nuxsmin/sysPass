<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

return [
    'fakerphp/faker' => [
        'version' => '1.24.1',
        'patterns' => [
            '/^Since fakerphp\/faker [^:]+: Accessing property "[A-Za-z0-9_]+" is deprecated, use "[A-Za-z0-9_]+\(\)" instead\.$/',
            '/^Since fakerphp\/faker [^:]+: Provider is deprecated and will no longer be available in Faker \d+\. Please use a custom provider instead\.?$/',
        ],
    ],
    'klein/klein' => [
        'version' => '2.1.2',
        'patterns' => [
            '/^Return type of Klein\\\\DataCollection\\\\DataCollection::(?:getIterator|offsetExists|offsetGet|offsetSet|offsetUnset|count)\(.*\) should either be compatible with .*$/',
            '/^Klein\\\\[A-Za-z0-9_\\\\]+::[A-Za-z0-9_]+\(\): Implicitly marking parameter \$[A-Za-z0-9_]+ as nullable is deprecated, the explicit nullable type must be used instead$/',
            '/^Non-canonical cast \(boolean\) is deprecated, use the \(bool\) cast instead$/',
        ],
    ],
];
