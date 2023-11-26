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

namespace SP\Modules\Web\Controllers\Status;

use SP\Core\Exceptions\CheckException;
use SP\Domain\Core\AppInfoInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Util\VersionUtil;
use Throwable;

/**
 * Class StatusController
 *
 * @package SP\Modules\Web\Controllers
 */
final class StatusController extends StatusBase
{
    use JsonTrait;

    private const TAG_VERSION_REGEX = '/v?(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)\.(?P<build>\d+)(?P<pre_release>\-[a-z0-9\.]+)?$/';

    /**
     * checkReleaseAction
     *
     * @return bool
     * @throws \JsonException
     */
    public function checkReleaseAction(): bool
    {
        try {
            $this->extensionChecker->checkCurlAvailable(true);

            $request = $this->client->request('GET', AppInfoInterface::APP_UPDATES_URL);

            if ($request->getStatusCode() === 200
                && strpos($request->getHeaderLine('content-type'), 'application/json') !== false
            ) {
                $requestData = json_decode($request->getBody(), false, 512, JSON_THROW_ON_ERROR);

                if ($requestData !== null && !isset($requestData->message)
                    && preg_match(self::TAG_VERSION_REGEX, $requestData->tag_name, $matches)) {
                    $pubVersion = $matches['major'].
                                  $matches['minor'].
                                  $matches['patch'].
                                  '.'.
                                  $matches['build'];

                    if (VersionUtil::checkVersion(VersionUtil::getVersionStringNormalized(), $pubVersion)) {
                        return $this->returnJsonResponseData([
                            'version'     => $requestData->tag_name,
                            'url'         => $requestData->html_url,
                            'title'       => $requestData->name,
                            'description' => $requestData->body,
                            'date'        => $requestData->published_at,
                        ]);
                    }

                    return $this->returnJsonResponseData([]);
                }

                logger($requestData->message);
            }

            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Version unavailable'));
        } catch (CheckException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Throwable $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}
