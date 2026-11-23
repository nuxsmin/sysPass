<?php
/**
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

namespace SP\Modules\Web\Forms;

use DateTimeImmutable;
use SP\Domain\Auth\Models\AuthToken;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Ports\RequestService;

use function SP\__u;

/**
 * Class ApiTokenForm
 *
 * @package SP\Modules\Web\Forms
 */
final class AuthTokenForm extends FormBase implements FormInterface
{
    protected ?AuthToken $authTokenData = null;
    protected bool       $refresh       = false;

    public function __construct(
        ConfigFileService            $config,
        Context                      $context,
        RequestService               $request,
        private readonly AclInterface $acl,
        ?int                         $itemId = null
    ) {
        parent::__construct($config, $context, $request, $itemId);
    }

    /**
     * Validar el formulario
     *
     * @param int $action
     * @param int|null $id
     *
     * @return FormInterface
     * @throws UnauthorizedActionException
     * @throws ValidationException
     */
    public function validateFor(int $action, ?int $id = null): FormInterface
    {
        if ($id !== null) {
            $this->itemId = $id;
        }

        switch ($action) {
            case AclActionsInterface::AUTHTOKEN_CREATE:
            case AclActionsInterface::AUTHTOKEN_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon($action);
                break;
        }

        return $this;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @throws ValidationException
     */
    protected function analyzeRequestData(): void
    {
        $this->refresh = $this->request->analyzeBool('refreshtoken', false);

        [$expireDate, $noExpiry] = $this->parseExpiryFields();

        $this->authTokenData = new AuthToken(
            [
                'id' => $this->itemId,
                'userId' => $this->request->analyzeInt('users'),
                'actionId' => $this->request->analyzeInt('actions'),
                'hash' => $this->request->analyzeEncrypted('pass'),
                'expireDate' => $expireDate,
                'noExpiry' => $noExpiry,
            ]
        );
    }

    /**
     * @return array{?int, ?bool}
     * @throws ValidationException
     */
    private function parseExpiryFields(): array
    {
        $rawInitial = $this->request->analyzeString('expire_date_initial');
        $rawUnix = $this->request->analyzeString('expire_date_unix');
        $rawDate = $this->request->analyzeString('expire_date');
        $noExpiryRequested = $this->request->analyzeBool('no_expiry', false);

        $trimmedDate = $rawDate !== null ? trim($rawDate) : null;
        $trimmedUnix = $rawUnix !== null ? trim($rawUnix) : null;
        $trimmedInitial = $rawInitial !== null ? trim($rawInitial) : null;

        $hasExplicitNewDate = ($trimmedDate !== null && $trimmedDate !== '' && ($trimmedInitial === null || $trimmedInitial === '__new__' || $trimmedDate !== $trimmedInitial))
            || ($trimmedUnix !== null && $trimmedUnix !== '');

        if ($noExpiryRequested) {
            if ($hasExplicitNewDate) {
                throw new ValidationException(__u('Cannot specify both an expiration date and no-expiry'));
            }

            return [null, true];
        }

        if ($trimmedUnix !== null && $trimmedUnix !== '') {
            if (preg_match('/^\d+$/', $trimmedUnix) !== 1 || (int)$trimmedUnix <= 0) {
                throw new ValidationException(__u('Invalid expiration date'));
            }

            return [(int)$trimmedUnix, false];
        }

        if ($trimmedDate !== null && $trimmedDate !== '') {
            if ($trimmedInitial !== null && $trimmedInitial !== '__new__' && $trimmedDate === $trimmedInitial) {
                return [null, null];
            }

            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $trimmedDate);

            if ($parsedDate === false || $parsedDate->format('Y-m-d') !== $trimmedDate) {
                throw new ValidationException(__u('Invalid expiration date'));
            }

            $now = time();
            $today = DateTimeImmutable::createFromFormat('!Y-m-d', date('Y-m-d', $now));
            $daysDiff = $today !== false ? (int)$today->diff($parsedDate)->format('%r%a') : 0;

            return [$now + ($daysDiff * 86400), false];
        }

        if ($trimmedInitial === 'never') {
            if ($this->context->getUserData()->isAdminApp) {
                return [null, false];
            }

            return [null, null];
        }

        if ($trimmedDate === '' || $trimmedInitial !== null) {
            return [null, false];
        }

        return [null, null];
    }

    /**
     * @throws UnauthorizedActionException
     * @throws ValidationException
     */
    protected function checkCommon(int $action = AclActionsInterface::AUTHTOKEN_CREATE): void
    {
        if (0 === $this->authTokenData->getUserId()) {
            throw new ValidationException(__u('User not set'));
        }

        if (0 === $this->authTokenData->getActionId()) {
            throw new ValidationException(__u('Action not set'));
        }

        $userData = $this->context->getUserData();

        if (!$userData->isAdminApp) {
            if ($this->authTokenData->getUserId() !== $userData->id
                || !$this->acl->checkUserAccess($this->authTokenData->getActionId())
            ) {
                throw new ValidationException(__u('You don\'t have permission to do this operation'));
            }

            if ($this->authTokenData->getNoExpiry() === true) {
                throw new UnauthorizedActionException();
            }
        }

        $isKeyDependent = AuthToken::isKeyDependentAction((int)$this->authTokenData->getActionId());
        $requiresPassword = $this->isRefresh()
            || ($action === AclActionsInterface::AUTHTOKEN_CREATE && $isKeyDependent);

        if (empty($this->authTokenData->getHash()) && $requiresPassword) {
            throw new ValidationException(__u('Password cannot be blank'));
        }
    }

    public function isRefresh(): bool
    {
        return $this->refresh;
    }

    public function getItemData(): ?AuthToken
    {
        return $this->authTokenData;
    }
}
