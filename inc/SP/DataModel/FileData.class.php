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
 * Class FileData
 *
 * @package SP\DataModel
 */
class FileData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $accfile_id = 0;
    /**
     * @var int
     */
    public $accfile_accountId = 0;
    /**
     * @var string
     */
    public $accfile_name = '';
    /**
     * @var int
     */
    public $accfile_type = 0;
    /**
     * @var string
     */
    public $accfile_content = '';
    /**
     * @var string
     */
    public $accfile_extension = '';
    /**
     * @var string
     */
    public $accfile_thumb = '';
    /**
     * @var int
     */
    public $accfile_size = 0;

    /**
     * @return int
     */
    public function getAccfileAccountId()
    {
        return $this->accfile_accountId;
    }

    /**
     * @param int $accfile_accountId
     */
    public function setAccfileAccountId($accfile_accountId)
    {
        $this->accfile_accountId = $accfile_accountId;
    }

    /**
     * @return string
     */
    public function getAccfileName()
    {
        return $this->accfile_name;
    }

    /**
     * @param string $accfile_name
     */
    public function setAccfileName($accfile_name)
    {
        $this->accfile_name = $accfile_name;
    }

    /**
     * @return int
     */
    public function getAccfileType()
    {
        return $this->accfile_type;
    }

    /**
     * @param int $accfile_type
     */
    public function setAccfileType($accfile_type)
    {
        $this->accfile_type = $accfile_type;
    }

    /**
     * @return string
     */
    public function getAccfileContent()
    {
        return $this->accfile_content;
    }

    /**
     * @param string $accfile_content
     */
    public function setAccfileContent($accfile_content)
    {
        $this->accfile_content = $accfile_content;
    }

    /**
     * @return string
     */
    public function getAccfileExtension()
    {
        return $this->accfile_extension;
    }

    /**
     * @param string $accfile_extension
     */
    public function setAccfileExtension($accfile_extension)
    {
        $this->accfile_extension = $accfile_extension;
    }

    /**
     * @return string
     */
    public function getAccfileThumb()
    {
        return $this->accfile_thumb;
    }

    /**
     * @param string $accfile_thumb
     */
    public function setAccfileThumb($accfile_thumb)
    {
        $this->accfile_thumb = $accfile_thumb;
    }

    /**
     * @return int
     */
    public function getAccfileSize()
    {
        return $this->accfile_size;
    }

    /**
     * @param int $accfile_size
     */
    public function setAccfileSize($accfile_size)
    {
        $this->accfile_size = $accfile_size;
    }

    /**
     * @return float
     */
    public function getRoundSize()
    {
        return round(($this->accfile_size / 1000), 2);
    }

    /**
     * @return int
     */
    public function getAccfileId()
    {
        return $this->accfile_id;
    }

    /**
     * @param int $accfile_id
     */
    public function setAccfileId($accfile_id)
    {
        $this->accfile_id = $accfile_id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->accfile_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->accfile_name;
    }
}