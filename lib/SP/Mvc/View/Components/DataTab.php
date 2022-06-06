<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Exceptions\FileNotFoundException;
use SP\Mvc\View\TemplateInterface;

/**
 * Class DataTab
 *
 * @package SP\Mvc\View\Components
 */
final class DataTab
{
    protected string            $title;
    protected TemplateInterface $template;

    public function __construct(string $title, TemplateInterface $template)
    {
        $this->title = $title;
        $this->template = $template;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): DataTab
    {
        $this->title = $title;

        return $this;
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