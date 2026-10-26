<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Account\Models\PublicLink;
use SP\Domain\Account\PublickLinkType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ValidationException;

use function SP\__u;

/**
 * Class PublicLinkForm
 *
 * @package SP\Modules\Web\Forms
 */
final class PublicLinkForm extends FormBase implements FormInterface
{
    protected ?PublicLink $publicLink = null;

    /**
     * Validar el formulario
     *
     * @param int $action
     * @param int|null $id
     *
     * @return PublicLinkForm
     * @throws ValidationException
     */
    public function validateFor(int $action, ?int $id = null): FormInterface
    {
        if ($id !== null) {
            $this->itemId = $id;
        }

        switch ($action) {
            case AclActionsInterface::PUBLICLINK_CREATE:
            case AclActionsInterface::PUBLICLINK_EDIT:
                $this->analyzeRequestData();
                $this->checkCommon();
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
        $this->publicLink = new PublicLink(
            [
                'id' => $this->itemId,
                'typeId' => PublickLinkType::Account->value,
                'itemId' => $this->request->analyzeInt('accountId'),
                'notify' => $this->request->analyzeBool('notify', false)
            ]
        );
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon(): void
    {
        if (!$this->publicLink->getItemId()) {
            throw new ValidationException(__u('An account is needed'));
        }
    }

    public function getItemData(): ?PublicLink
    {
        return $this->publicLink;
    }
}
