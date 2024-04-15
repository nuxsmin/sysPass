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

namespace SP\Domain\Auth\Services;

use Exception;
use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Http\Uri;

use function SP\__u;

/**
 * Class LoginBase
 */
abstract class LoginBase extends Service
{
    private TrackRequest $trackRequest;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        Application                         $application,
        private readonly TrackService       $trackService,
        protected readonly RequestInterface $request
    ) {
        parent::__construct($application);

        $this->trackRequest = $this->trackService->buildTrackRequest(static::class);
    }

    /**
     * @throws AuthException
     * @throws Exception
     */
    final protected function checkTracking(): void
    {
        if ($this->trackService->checkTracking($this->trackRequest)) {
            $this->addTracking();

            throw AuthException::error(__u('Attempts exceeded'), null, LoginStatus::MAX_ATTEMPTS_EXCEEDED->value);
        }
    }

    /**
     * Añadir un seguimiento
     *
     * @throws AuthException
     */
    final protected function addTracking(): void
    {
        try {
            $this->trackService->add($this->trackRequest);
        } catch (Exception $e) {
            throw AuthException::error(__u('Internal error'), null, Service::STATUS_INTERNAL_ERROR, $e);
        }
    }

    protected function getUriForRoute(string $route): string
    {
        return (new Uri('index.php'))->addParam('r', $route)->getUri();
    }
}
