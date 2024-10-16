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

namespace SP\Domain\Http\Services;

use Exception;
use Klein\DataCollection\DataCollection;
use Klein\DataCollection\HeaderDataCollection;
use SP\Core\Crypt\Hash;
use SP\Domain\Common\Providers\Filter;
use SP\Domain\Core\Crypt\CryptPKIHandler;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Header;
use SP\Domain\Http\Method;
use SP\Domain\Http\Ports\RequestService;
use SP\Infrastructure\File\FileSystem;
use SP\Util\Util;

use function SP\logger;
use function SP\processException;

/**
 * Class Request
 */
class Request implements RequestService
{
    /**
     * @var array Directorios seguros para include
     */
    public const SECURE_DIRS = ['css', 'js'];

    private HeaderDataCollection $headers;
    private DataCollection       $params;
    private Method $method;
    private ?bool  $https = null;

    /**
     * Request constructor.
     */
    public function __construct(private readonly \Klein\Request $request, private readonly CryptPKIHandler $cryptPKI)
    {
        $this->headers = $this->request->headers();
        $this->method = Method::from($this->request->method());
        $this->params = $this->getParamsForMethod();
        $this->detectHttps();
    }

    private function getParamsForMethod(): DataCollection
    {
        return match ($this->method) {
            Method::GET => $this->request->paramsGet(),
            Method::POST => $this->request->paramsPost()
        };
    }

    /**
     * Detects if the connection is done through HTTPS
     */
    private function detectHttps(): void
    {
        $server = $this->request->server();

        $this->https = Util::boolval($server->get('HTTPS', 'off'))
                       || $server->get('SERVER_PORT', 0) === 443;
    }

    /**
     * Devuelve un nombre de archivo seguro
     */
    public static function getSecureAppFile(string $file, ?string $base = null): string
    {
        return basename(self::getSecureAppPath($file, $base));
    }

    /**
     * Devolver una ruta segura para
     */
    public static function getSecureAppPath(string $path, ?string $base = null): string
    {
        if ($base === null) {
            $base = APP_ROOT;
        } elseif (!in_array(basename($base), self::SECURE_DIRS, true)) {
            return '';
        }

        $realPath = realpath(FileSystem::buildPath($base, $path));

        if ($realPath === false || !str_starts_with($realPath, $base)) {
            return '';
        }

        return $realPath;
    }

    public function getClientAddress(bool $fullForwarded = false): string
    {
        $forwarded = $this->getForwardedFor();

        if ($forwarded !== null) {
            return $fullForwarded
                ? implode(',', $forwarded)
                : $forwarded[0];
        }

        return $this->request->server()->get('REMOTE_ADDR', '');
    }

    /**
     * @return string[]|null
     */
    public function getForwardedFor(): ?array
    {
        // eg: Forwarded: by=<identifier>; for=<identifier>; host=<host>; proto=<http|https>
        // Forwarded: for=12.34.56.78;host=example.com;proto=https,for=23.45.67.89
        $forwarded = $this->headers->get(Header::HTTP_FORWARDED->value);

        if ($forwarded !== null
            && preg_match_all(
                '/for="?\[?([\w.:]+)]?"?/',
                $forwarded,
                $matches
            )
        ) {
            return array_filter(
                $matches[1],
                static fn($value) => !empty($value)
            );
        }

        // eg: X-Forwarded-For: 192.0.2.43, 2001:db8:cafe::17
        $xForwarded = $this->headers->get(Header::HTTP_X_FORWARDED_FOR->value);

        if ($xForwarded !== null) {
            $matches = preg_split(
                '/(?<=\w)+,\s?/',
                $xForwarded,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            if (count($matches) > 0) {
                return $matches;
            }
        }

        return null;
    }

    /**
     * Comprobar si se realiza una recarga de la página
     */
    public function checkReload(): bool
    {
        return $this->headers->get(Header::CACHE_CONTROL->value) === 'max-age=0';
    }

    public function analyzeEmail(string $param, ?string $default = null): ?string
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getEmail($this->params->get($param));
    }

    /**
     * Analizar un valor encriptado y devolverlo desencriptado
     */
    public function analyzeEncrypted(string $param): ?string
    {
        $encryptedData = $this->analyzeString($param);

        if ($encryptedData === null) {
            return null;
        }

        try {
            $clearData = $this->cryptPKI->decryptRSA(base64_decode($encryptedData));

            if ($clearData === null) {
                logger('No RSA encrypted data from request');

                return $encryptedData;
            }

            return $clearData;
        } catch (Exception $e) {
            processException($e);

            return $encryptedData;
        }
    }

    public function analyzeString(string $param, ?string $default = null): ?string
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getString($this->params->get($param));
    }

    public function analyzeUnsafeString(string $param, ?string $default = null): ?string
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getRaw($this->params->get($param));
    }

    /**
     * @param string $param
     * @param callable|null $mapper
     * @param null $default
     *
     * @return array|null
     */
    public function analyzeArray(
        string $param,
        ?callable $mapper = null,
        mixed $default = null
    ): ?array {
        $requestValue = $this->params->get($param);

        if (is_array($requestValue)) {
            if ($mapper !== null) {
                return $mapper($requestValue);
            }

            return Filter::getArray($requestValue);
        }

        return $default;
    }

    /**
     * Comprobar si la petición es en formato JSON
     */
    public function isJson(): bool
    {
        return str_contains($this->headers->get(Header::ACCEPT->value), Header::ACCEPT_JSON->value);
    }

    /**
     * Comprobar si la petición es Ajax
     */
    public function isAjax(): bool
    {
        return $this->headers->get(Header::X_REQUESTED_WITH->value) === 'XMLHttpRequest'
               || $this->analyzeInt('isAjax', 0) === 1;
    }

    public function analyzeInt(string $param, ?int $default = null): ?int
    {
        if (!$this->params->exists($param)) {
            return $default;
        }

        return Filter::getInt($this->params->get($param));
    }

    public function getFile(string $file): ?array
    {
        return $this->request->files()->get($file);
    }

    public function analyzeBool(string $param, ?bool $default = null): bool
    {
        if (!$this->params->exists($param)) {
            return (bool)$default;
        }

        return Util::boolval($this->params->get($param));
    }

    /**
     * @param string $key
     * @param string|null $param Checks the signature only for the given param
     *
     * @throws SPException
     */
    public function verifySignature(string $key, ?string $param = null): void
    {
        $result = false;
        $hash = $this->params->get('h');

        if ($hash !== null) {
            // Strips out the hash param from the URI to get the
            // route which will be checked against the computed HMAC
            if ($param === null) {
                $uri = implode('&', $this->request->params('h'));
            } else {
                $uri = $this->params->get($param, '');
            }

            $result = Hash::checkMessage($uri, $key, $hash);
        }

        if ($result === false) {
            throw new SPException(
                'URI string altered',
                SPException::ERROR,
                null,
                1
            );
        }
    }

    /**
     * Returns the URI used by the browser and checks for the protocol used
     *
     * @see https://tools.ietf.org/html/rfc7239#section-7.5
     */
    public function getHttpHost(): string
    {
        // Check in style of RFC 7239 otherwise the deprecated standard
        $forwarded = $this->getForwardedData() ?? $this->getXForwardedData();

        if (null !== $forwarded) {
            return strtolower(sprintf('%s://%s', $forwarded['proto'], $forwarded['host']));
        }

        /** @noinspection HttpUrlsUsage */
        $protocol = 'http://';

        // We got called directly
        if ($this->https) {
            $protocol = 'https://';
        }

        return sprintf('%s%s', $protocol, $this->request->server()->get('HTTP_HOST'));
    }

    /**
     * Devolver datos de forward RFC 7239
     *
     * @see https://tools.ietf.org/html/rfc7239#section-7.5
     */
    public function getForwardedData(): ?array
    {
        $forwarded = $this->getHeader(Header::HTTP_FORWARDED->value);

        // Check in style of RFC 7239
        if (!empty($forwarded)
            && preg_match_all(
                '/proto=(?P<proto>(\w+))|host=(?P<host>([\w.]+))/i',
                $forwarded,
                $matches
            )
        ) {
            $mapper = static fn(array $values): string => (string)current(
                array_filter($values, static fn(mixed $value) => !empty($value))
            );

            $data = [
                'host' => $mapper($matches['host']),
                'proto' => $mapper($matches['proto']),
                'for' => $this->getForwardedFor(),
            ];

            // Check if protocol and host are not empty
            if (!empty($data['proto']) && !empty($data['host'])) {
                return $data;
            }
        }

        return null;
    }

    public function getHeader(string $header): string
    {
        return $this->headers->get($header, '');
    }

    /**
     * Devolver datos de x-forward
     */
    public function getXForwardedData(): ?array
    {
        $clean = static fn(string $value) => trim(str_replace('"', '', $value));

        $forwardedHost = $clean($this->getHeader(Header::HTTP_X_FORWARDED_HOST->value));
        $forwardedProto = $clean($this->getHeader(Header::HTTP_X_FORWARDED_PROTO->value));

        // Check (deprecated) de facto standard
        if (!empty($forwardedHost) && !empty($forwardedProto)) {
            return [
                'host' => $forwardedHost,
                'proto' => $forwardedProto,
                'for' => $this->getForwardedFor(),
            ];
        }

        return null;
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function isHttps(): ?bool
    {
        return $this->https;
    }

    public function getServerPort(): int
    {
        return (int)$this->request->server()->get('SERVER_PORT', 80);
    }

    public function getRequest(): \Klein\Request
    {
        return $this->request;
    }

    public function getServer(string $key): string
    {
        return (string)$this->request->server()->get($key, '');
    }
}
