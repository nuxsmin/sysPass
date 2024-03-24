<?php
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

namespace SP\Core;

use RuntimeException;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\PhpExtensionCheckerService;

use function SP\__u;
use function SP\logger;

/**
 * Class PhpExtensionChecker
 */
final class PhpExtensionChecker implements PhpExtensionCheckerService
{
    /**
     * Array of extensions needed by sysPass.
     *
     * true  -> required
     * false -> not required
     */
    public const EXTENSIONS = [
        'ldap' => false,
        'curl' => false,
        'simplexml' => false,
        'libxml' => true,
        'phar' => false,
        'json' => true,
        'xml' => true,
        'pdo' => true,
        'zlib' => false,
        'gettext' => true,
        'openssl' => true,
        'pcre' => true,
        'session' => true,
        'gd' => false,
        'mbstring' => true,
        'pdo_mysql' => true,
        'fileinfo' => true
    ];

    public const MSG_NOT_AVAILABLE = 'Oops, it seems that some extensions are not available: \'%s\'';

    /**
     * Available extensions
     */
    private ?array $available = null;

    /**
     * PhpExtensionChecker constructor.
     */
    public function __construct()
    {
        $this->checkExtensions();
    }

    /**
     * Check for available extensions
     */
    private function checkExtensions(): void
    {
        $this->available = array_intersect(
            array_keys(self::EXTENSIONS),
            array_map('strtolower', get_loaded_extensions())
        );
    }

    /**
     * @throws CheckException
     */
    public function __call(string $name, array $arguments)
    {
        if (str_contains($name, 'check')) {
            $extension = strtolower(str_replace('check', '', $name));

            return $this->checkIsAvailable($extension, ...$arguments);
        } else {
            throw new RuntimeException(__u('Unknown magic method'));
        }
    }

    /**
     * Checks if the extension is installed
     *
     * @param string $extension
     * @param bool $exception Throws an exception if the extension is not available
     *
     * @return bool
     * @throws CheckException
     */
    public function checkIsAvailable(
        string $extension,
        bool   $exception = false
    ): bool {
        $result = in_array(strtolower($extension), $this->available, true);

        if (!$result && $exception) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, $extension));
        }

        return $result;
    }

    /**
     * @throws CheckException
     */
    public function checkMandatory(): void
    {
        $missing = array_filter(
            self::EXTENSIONS,
            function ($v, $k) {
                return $v === true && !in_array($k, $this->available, true);
            },
            ARRAY_FILTER_USE_BOTH
        );

        if (count($missing) > 0) {
            throw new CheckException(sprintf(self::MSG_NOT_AVAILABLE, implode(',', array_keys($missing))));
        }

        logger('Extensions checked', 'INFO');
    }

    /**
     * Returns missing extensions
     */
    public function getMissing(): array
    {
        return array_filter(
            self::EXTENSIONS,
            function ($k) {
                return !in_array($k, $this->available, true);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
}
