<?php
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

declare(strict_types=1);

namespace SP\Domain\Common\Dtos;

use Closure;
use JsonSerializable;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Common\Enums\ResponseStatus;
use SP\Domain\Core\Exceptions\SPException;
use stdClass;

use function SP\__;

/**
 * Class ActionResponse
 */
final readonly class ActionResponse implements JsonSerializable
{

    public function __construct(
        public ResponseStatus             $status,
        public array|string|Closure $subject,
        public array|string|stdClass|null $extra = null
    ) {
    }

    public static function ok(array|string|stdClass $subject, array|string|null $extra = null): ActionResponse
    {
        return new self(ResponseStatus::OK, $subject, $extra);
    }

    public static function error(array|string|stdClass $subject, array|string|null $extra = null): ActionResponse
    {
        return new self(ResponseStatus::ERROR, $subject, $extra);
    }

    public static function warning(array|string|stdClass $subject, array|string|null $extra = null): ActionResponse
    {
        return new self(ResponseStatus::WARNING, $subject, $extra);
    }

    /**
     * @throws SPException
     */
    public static function toJson(ActionResponse $actionResponse): string
    {
        return Serde::serializeJson($actionResponse);
    }

    public static function toPlain(ActionResponse $actionResponse): string
    {
        return match (gettype($actionResponse->subject)) {
            'string' => $actionResponse->subject,
            'array' => implode(PHP_EOL, $actionResponse->subject)
        };
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return array data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status->name,
            'description' => $this->adaptSubject(),
            'data' => $this->extra,
        ];
    }

    private function adaptSubject(): string|array
    {
        return match (gettype($this->subject)) {
            'string' => __($this->subject),
            'array' => array_map('SP\__', $this->subject)
        };
    }
}
