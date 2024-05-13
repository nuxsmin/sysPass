<?php
/**
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

declare(strict_types=1);

namespace SP\Core\Context;

use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Exceptions\SPException;

use function SP\__u;
use function SP\logger;

/**
 * Class SessionLifecycleHandler
 */
final class SessionLifecycleHandler
{
    private const DESTROY_TIME_KEY   = 'destroy_time';
    private const SESSION_OPTIONS    = [
        'use_strict_mode' => '1',
        'cookie_lifetime' => '0',
        'use_cookies' => '1',
        'use_only_cookies' => '1',
        'cookie_httponly' => '1',
        'cookie_secure' => '1',
        'cookie_samesite' => 'Strict',
        'use_trans_sid' => '0',
        'cache_limiter' => 'nocache',
        'sid_bits_per_character' => '6'
    ];
    private const NEW_ID_KEY         = 'new_session_id';
    private const DESTROY_TIMEOUT    = 300;
    private const REGENERATE_TIMEOUT = 120;

    /**
     * Clean up the session and destroy it
     *
     * @throws SPException
     */
    public static function clean(): void
    {
        self::start();
        self::destroy();
    }

    /**
     * Start a session
     * @throws SPException
     */
    public static function start(): void
    {
        if (session_status() != PHP_SESSION_ACTIVE
            && (headers_sent($filename, $line)
                || session_start(self::SESSION_OPTIONS)) === false
        ) {
            logger(sprintf('Headers sent in %s:%d file', $filename, $line));

            throw SPException::error(__u('Session cannot be initialized'));
        }

        if (isset($_SESSION[self::DESTROY_TIME_KEY])) {
            if ($_SESSION[self::DESTROY_TIME_KEY] < time() - self::DESTROY_TIMEOUT) {
                session_destroy();
                session_start(self::SESSION_OPTIONS);
            }

            if (isset($_SESSION[self::NEW_ID_KEY])) {
                session_commit();
                session_id($_SESSION[self::NEW_ID_KEY]);
                session_start(self::optionsWithoutStrictMode());
            }
        }
    }

    /**
     * @return string[]
     */
    private static function optionsWithoutStrictMode(): array
    {
        return array_merge(self::SESSION_OPTIONS, ['use_strict_mode' => '0']);
    }

    /**
     * @return void
     */
    private static function destroy(): void
    {
        $_SESSION = [];

        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );

        session_destroy();
    }

    /**
     * Regenerate session ID
     * @throws SPException
     */
    public static function regenerate(): string
    {
        self::start();

        $sessionId = session_create_id(sprintf('%s-', AppInfoInterface::APP_NAME));

        $_SESSION[self::NEW_ID_KEY] = $sessionId;
        $_SESSION[self::DESTROY_TIME_KEY] = time();

        session_commit();
        session_id($sessionId);
        session_start(self::optionsWithoutStrictMode());

        unset($_SESSION[self::DESTROY_TIME_KEY]);
        unset($_SESSION[self::NEW_ID_KEY]);

        return $sessionId;
    }

    /**
     * @throws SPException
     */
    public static function restart(): void
    {
        self::start();
        self::destroy();
        self::start();
    }

    public static function needsRegenerate(int $since): bool
    {
        return $since < time() - self::REGENERATE_TIMEOUT;
    }
}
