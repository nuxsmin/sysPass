<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\DataModel;

defined('APP_ROOT') || die();

/**
 * Class CategoryData
 *
 * @package SP\DataModel
 */
class CategoryData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $category_id = 0;
    /**
     * @var string
     */
    public $category_name = '';
    /**
     * @var string
     */
    public $category_description = '';
    /**
     * @var string
     */
    public $category_hash = '';

    /**
     * CategoryData constructor.
     *
     * @param int    $category_id
     * @param string $category_name
     * @param string $category_description
     */
    public function __construct($category_id = null, $category_name = null, $category_description = null)
    {
        $this->category_id = $category_id;
        $this->category_name = $category_name;
        $this->category_description = $category_description;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * @param int $category_id
     * @return $this
     */
    public function setCategoryId($category_id)
    {
        $this->category_id = $category_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * @param string $category_name
     */
    public function setCategoryName($category_name)
    {
        $this->category_name = $category_name;
    }

    /**
     * @return string
     */
    public function getCategoryDescription()
    {
        return $this->category_description;
    }

    /**
     * @param string $category_description
     */
    public function setCategoryDescription($category_description)
    {
        $this->category_description = $category_description;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->category_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->category_name;
    }

    /**
     * @return string
     */
    public function getCategoryHash()
    {
        return $this->category_hash;
    }
}