<?php
declare(strict_types=1);
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

namespace SP\Mvc\View\Components;

use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Mvc\View\TemplateInterface;

/**
 * Class DataTab
 */
final readonly class DataTab
{

    public function __construct(protected string $title, protected TemplateInterface $template)
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function render(): string
    {
        try {
            return $this->template->render();
        } catch (FileNotFoundException $e) {
            return $e->getMessage();
        }
    }
}
