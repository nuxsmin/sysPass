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

namespace SP\Modules\Web\Controllers\Error;

use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\processException;

/**
 * Class ErrorBase
 */
abstract class ErrorBase
{
    protected TemplateInterface $view;
    protected LayoutHelper $layoutHelper;

    public function __construct(TemplateInterface $template, LayoutHelper $layoutHelper)
    {
        $this->view = $template;
        $this->layoutHelper = $layoutHelper;
    }

    final protected function render(): string
    {
        try {
            return $this->view->render();
        } catch (FileNotFoundException $e) {
            processException($e);

            return $e->getMessage();
        }
    }
}
