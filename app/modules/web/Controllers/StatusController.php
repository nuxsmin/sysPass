<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Modules\Web\Controllers;

use GuzzleHttp\Client;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Util\Util;

/**
 * Class StatusController
 *
 * @package SP\Modules\Web\Controllers
 */
class StatusController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * checkReleaseAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkReleaseAction()
    {
        $request = $this->dic->get(Client::class)
            ->request('GET', Util::getAppInfo('appupdates'));

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

                    if (Util::checkVersion(Util::getVersionStringNormalized(), $pubVersion)) {
                        $this->returnJsonResponseData([
                            'version' => $requestData->tag_name,
                            'url' => $requestData->html_url,
                            'title' => $requestData->name,
                            'description' => $requestData->body,
                            'date' => $requestData->published_at
                        ]);
                    }

                    $this->returnJsonResponseData([]);
                }
            }

            debugLog($requestData->message);
        }

        $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Versión no disponible'));
    }

    /**
     * checkNoticesAction
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkNoticesAction()
    {
        $request = $this->dic->get(Client::class)
            ->request('GET', Util::getAppInfo('appnotices'));

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

                $this->returnJsonResponseData($notices);
            }

            debugLog($requestData->message);
        }

        $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Notificaciones no disponibles'));
    }
}