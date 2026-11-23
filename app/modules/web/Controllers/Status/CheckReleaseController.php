<?php
/*
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

namespace SP\Modules\Web\Controllers\Status;

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Core\AppInfoInterface;
use Throwable;

use function SP\__u;

/**
 * Checks for a newer GitHub release.
 */
final class CheckReleaseController extends StatusBase
{
    private const TAG_VERSION_REGEX = '/v?(?P<major>\d+)\.(?P<minor>\d+)\.(?P<patch>\d+)\.(?P<build>\d+)(?P<pre_release>\-[a-z0-9\.]+)?$/';

    #[Action(ResponseType::JSON)]
    public function checkReleaseAction(): ActionResponse
    {
        $this->checks();

        if (!$this->session->getUserData()->isAdminApp) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->extensionChecker->checkCurl(true);
            $request = $this->client->request('GET', AppInfoInterface::APP_UPDATES_URL);

            if ($request->getStatusCode() !== 200
                || strpos($request->getHeaderLine('content-type'), 'application/json') === false
            ) {
                return $this->warning();
            }

            $requestData = json_decode($request->getBody(), false, 512, JSON_THROW_ON_ERROR);

            if ($requestData === null || !isset($requestData->tag_name)
                || !preg_match(self::TAG_VERSION_REGEX, $requestData->tag_name, $matches)
            ) {
                return $this->warning();
            }

            $pubVersion = $matches['major'].$matches['minor'].$matches['patch'].'.'.$matches['build'];

            if (!Version::checkVersion(Version::getVersionStringNormalized(), $pubVersion)) {
                return ActionResponse::ok('');
            }

            if (!$this->isGitHubReleaseUrl($requestData->html_url ?? null)) {
                return $this->warning();
            }

            return ActionResponse::ok('', [
                'version' => $requestData->tag_name,
                'url' => $requestData->html_url,
                'title' => is_string($requestData->name ?? null) ? $requestData->name : '',
                'description' => is_string($requestData->body ?? null) ? $requestData->body : '',
                'date' => is_string($requestData->published_at ?? null) ? $requestData->published_at : '',
            ]);
        } catch (Throwable) {
            return $this->warning();
        }
    }

    private function isGitHubReleaseUrl(mixed $url): bool
    {
        if (!is_string($url)) {
            return false;
        }

        $parts = parse_url($url);

        return is_array($parts)
            && strtolower($parts['scheme'] ?? '') === 'https'
            && strtolower($parts['host'] ?? '') === 'github.com'
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && str_starts_with($parts['path'] ?? '', '/nuxsmin/sysPass/releases/');
    }

    private function warning(): ActionResponse
    {
        return ActionResponse::warning(__u('Version unavailable'));
    }
}
