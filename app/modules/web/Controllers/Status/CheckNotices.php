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
use Throwable;

/**
 * Class CheckNotices
 */
final class CheckNotices extends StatusBase
{
    use JsonTrait;

    /**
     * checkNoticesAction
     *
     * @return bool
     * @throws \JsonException
     */
    public function checkNoticesAction(): bool
    {
        try {
            $this->extensionChecker->checkCurlAvailable(true);

            $request = $this->client->request('GET', AppInfoInterface::APP_NOTICES_URL);

            if ($request->getStatusCode() === 200
                && strpos($request->getHeaderLine('content-type'), 'application/json') !== false
            ) {
                $requestData = json_decode($request->getBody(), false, 512, JSON_THROW_ON_ERROR);

                if ($requestData !== null && !isset($requestData->message)) {
                    $notices = [];

                    foreach ($requestData as $notice) {
                        $notices[] = [
                            'title' => $notice->title,
                            'date'  => $notice->created_at,
                            'text'  => $notice->body,
                        ];
                    }

                    return $this->returnJsonResponseData($notices);
                }

                logger($requestData->message);
            }

            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Notifications not available'));
        } catch (CheckException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Throwable $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}
