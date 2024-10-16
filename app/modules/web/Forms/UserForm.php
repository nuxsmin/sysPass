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

namespace SP\Modules\Web\Forms;

use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\User\Models\User;

/**
 * Class UserForm
 *
 * @package SP\Modules\Web\Forms
 */
final class UserForm extends FormBase implements FormInterface
{
    protected ?User $userData = null;
    protected int   $isLdap   = 0;

    /**
     * Validar el formulario
     *
     * @param  int  $action
     * @param  int|null  $id
     *
     * @return UserForm|FormInterface
     * @throws ValidationException
     */
    public function validateFor(int $action, ?int $id = null): FormInterface
    {
        if ($id !== null) {
            $this->itemId = $id;
        }

        switch ($action) {
            case AclActionsInterface::USER_CREATE:
                $this->analyzeRequestData();
                $this->checkCommon();
                $this->checkPass();
                break;
            case AclActionsInterface::USER_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
                break;
            case AclActionsInterface::USER_EDIT_PASS:
                $this->analyzeRequestData();
                $this->checkPass();
                break;
            case AclActionsInterface::USER_DELETE:
                $this->checkDelete();
                break;
        }

        return $this;
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    protected function analyzeRequestData(): void
    {
        $this->isLdap = $this->request->analyzeInt('isLdap', 0);

        $this->userData = new User();
        $this->userData->setId($this->itemId);
        $this->userData->setName($this->request->analyzeString('name'));
        $this->userData->setLogin($this->request->analyzeString('login'));
        $this->userData->setSsoLogin($this->request->analyzeString('login_sso'));
        $this->userData->setEmail($this->request->analyzeEmail('email'));
        $this->userData->setNotes($this->request->analyzeUnsafeString('notes'));
        $this->userData->setUserGroupId($this->request->analyzeInt('usergroup_id'));
        $this->userData->setUserProfileId($this->request->analyzeInt('userprofile_id'));
        $this->userData->setIsAdminApp($this->request->analyzeBool('adminapp_enabled', false));
        $this->userData->setIsAdminAcc($this->request->analyzeBool('adminacc_enabled', false));
        $this->userData->setIsDisabled($this->request->analyzeBool('disabled', false));
        $this->userData->setIsChangePass($this->request->analyzeBool('changepass_enabled', false));
        $this->userData->setPass($this->request->analyzeEncrypted('password'));
        $this->userData->setIsLdap($this->isLdap);
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon(): void
    {
        if (!$this->isLdap && !$this->userData->getName()) {
            throw new ValidationException(__u('An username is needed'));
        }

        if (!$this->isLdap && !$this->userData->getLogin()) {
            throw new ValidationException(__u('A login is needed'));
        }

        if (!$this->userData->getUserProfileId()) {
            throw new ValidationException(__u('A profile is needed'));
        }

        if (!$this->userData->getUserGroupId()) {
            throw new ValidationException(__u('A group is needed'));
        }

        if (!$this->isLdap && !$this->userData->getEmail()) {
            throw new ValidationException(__u('An email is needed'));
        }

        if ($this->isDemo()) {
            throw new ValidationException(__u('Ey, this is a DEMO!!'));
        }
    }

    /**
     * @return bool
     */
    private function isDemo(): bool
    {
        return $this->configData->isDemoEnabled()
               && $this->itemId === 2 // FIXME: Ugly!!
               && $this->context->getUserData()->getIsAdminApp();
    }

    /**
     * @throws ValidationException
     */
    protected function checkPass(): void
    {
        $userPassR = $this->request->analyzeEncrypted('password_repeat');

        if ($this->isDemo()) {
            throw new ValidationException(__u('Ey, this is a DEMO!!'));
        }

        if (!$userPassR || !$this->userData->getPass()) {
            throw new ValidationException(__u('Password cannot be blank'));
        }

        if ($userPassR !== $this->userData->getPass()) {
            throw new ValidationException(__u('Passwords do not match'));
        }
    }

    /**
     * @throws ValidationException
     */
    protected function checkDelete(): void
    {
        if ($this->isDemo()) {
            throw new ValidationException(__u('Ey, this is a DEMO!!'));
        }

        $userData = $this->context->getUserData();

        if ($this->itemId === $userData->getId()
            || (is_array($this->itemId)
                && in_array($userData->getId(), $this->itemId, true))
        ) {
            throw new ValidationException(__u('Unable to delete, user in use'));
        }
    }

    /**
     * @throws SPException
     */
    public function getItemData(): User
    {
        if (null === $this->userData) {
            throw new SPException(__u('User data not set'));
        }

        return $this->userData;
    }

    public function getIsLdap(): int
    {
        return $this->isLdap;
    }
}
