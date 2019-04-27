<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Mvc\View\Components;

use SP\Core\Exceptions\FileNotFoundException;
use SP\Mvc\View\Template;

/**
 * Class DataTab
 *
 * @package SP\Mvc\View\Components
 */
final class DataTab
{
    /**
     * @var string
     */
    protected $title;
    /**
     * @var Template
     */
    protected $template;

    /**
     * DataTab constructor.
     *
     * @param string   $title
     * @param Template $template
     */
    public function __construct($title, Template $template)
    {
        $this->title = $title;
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return DataTab
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        try {
            return $this->template->render();
        } catch (FileNotFoundException $e) {
            return $e->getMessage();
        }
    }

}