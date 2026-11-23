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

use SP\Domain\Category\Models\Category;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ValidationException;

use function SP\__u;

/**
 * Class CategoryForm
 *
 * @package SP\Modules\Web\Forms
 */
final class CategoryForm extends FormBase implements FormInterface
{
    protected ?Category $categoryData = null;

    /**
     * Validar el formulario
     *
     * @param int $action
     * @param int|null $id
     *
     * @return FormInterface
     * @throws ValidationException
     */
    public function validateFor(int $action, ?int $id = null): FormInterface
    {
        if ($id !== null) {
            $this->itemId = $id;
        }

        switch ($action) {
            case AclActionsInterface::CATEGORY_CREATE:
            case AclActionsInterface::CATEGORY_EDIT:
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
        $this->categoryData = new Category(
            [
                'id' => $this->itemId,
                'name' => $this->request->analyzeString('name'),
                'description' => $this->request->analyzeString('description')
            ]
        );
    }

    /**
     * @throws ValidationException
     */
    protected function checkCommon(): void
    {
        if (!$this->categoryData->getName()) {
            throw new ValidationException(__u('A category name needed'));
        }
    }

    public function getItemData(): ?Category
    {
        return $this->categoryData;
    }
}
