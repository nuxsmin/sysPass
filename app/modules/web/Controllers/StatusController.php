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

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SP\Core\AppInfoInterface;
use SP\Core\Exceptions\CheckException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Util\VersionUtil;

/**
 * Class StatusController
 *
 * @package SP\Modules\Web\Controllers
 */
final class StatusController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * checkReleaseAction
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function checkReleaseAction()
    {
        try {
            $this->extensionChecker->checkCurlAvailable(true);

            $request = $this->dic->get(Client::class)
                ->request('GET', AppInfoInterface::APP_UPDATES_URL);

            if ($request->getStatusCode() === 200
                && strpos($request->getHeaderLine('content-type'), 'application/json') !== false
            ) {
                $requestData = json_decode($request->getBody());

                if ($requestData !== null && !isset($requestData->message)) {
                    // $updateInfo[0]->tag_name
                    // $updateInfo[0]->name
                    // $updateInfo[0]->body
                    // $updateInfo[0]->tarball_url
                    // $updateInfo[0]->zipball_url
                    // $updateInfo[0]->published_at
                    // $updateInfo[0]->html_url

                    if (preg_match('/v?(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)\.(?P<build>\d+)(?P<pre_release>\-[a-z0-9\.]+)?$/', $requestData->tag_name, $matches)) {
                        $pubVersion = $matches['major'] . $matches['minor'] . $matches['patch'] . '.' . $matches['build'];

                        if (VersionUtil::checkVersion(VersionUtil::getVersionStringNormalized(), $pubVersion)) {
                            return $this->returnJsonResponseData([
                                'version' => $requestData->tag_name,
                                'url' => $requestData->html_url,
                                'title' => $requestData->name,
                                'description' => $requestData->body,
                                'date' => $requestData->published_at
                            ]);
                        }

                        return $this->returnJsonResponseData([]);
                    }
                }

                logger($requestData->message);
            }

            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Version unavailable'));
        } catch (GuzzleException $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        } catch (CheckException $e) {
            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * checkNoticesAction
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function checkNoticesAction()
    {
        try {
            $this->extensionChecker->checkCurlAvailable(true);

            $request = $this->dic->get(Client::class)
                ->request('GET', AppInfoInterface::APP_NOTICES_URL);

            if ($request->getStatusCode() === 200
                && strpos($request->getHeaderLine('content-type'), 'application/json') !== false
            ) {
                $requestData = json_decode($request->getBody());

                if ($requestData !== null && !isset($requestData->message)) {
                    $notices = [];

                    foreach ($requestData as $notice) {
                        $notices[] = [
                            'title' => $notice->title,
                            'date' => $notice->created_at,
                            'text' => $notice->body
                        ];
                    }

                    return $this->returnJsonResponseData($notices);
                }

                logger($requestData->message);
            }

            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Notifications not available'));
        } catch (GuzzleException $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        } catch (CheckException $e) {
            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return void
     */
    protected function initialize()
    {
        // TODO: Implement initialize() method.
    }
}