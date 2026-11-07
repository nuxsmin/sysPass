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

namespace SP\Modules\Web\Controllers\Status;

use JsonException;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Exceptions\CheckException;
use Throwable;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class CheckNotices
 */
final class CheckNoticesController extends StatusBase
{
    /**
     * @throws JsonException
     */
    #[Action(ResponseType::JSON)]
    public function checkNoticesAction(): ActionResponse
    {
        try {
            $this->extensionChecker->checkCurl(true);

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

                    return ActionResponse::ok('', $notices);
                }

                logger($requestData->message);
            }

            return ActionResponse::error(__u('Notifications not available'));
        } catch (CheckException $e) {
            return ActionResponse::error($e->getMessage(), $e->getHint());
        } catch (Throwable $e) {
            processException($e);

            return ActionResponse::error($e->getMessage());
        }
    }
}
