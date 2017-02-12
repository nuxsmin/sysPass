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
 * Class ProfileBaseData
 *
 * @package SP\DataModel
 */
class ProfileBaseData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $userprofile_id = 0;
    /**
     * @var string
     */
    public $userprofile_name = '';
    /**
     * @var ProfileData
     */
    public $userprofile_profile;

    /**
     * @return string
     */
    public function getUserprofileName()
    {
        return $this->userprofile_name;
    }

    /**
     * @param string $userprofile_name
     */
    public function setUserprofileName($userprofile_name)
    {
        $this->userprofile_name = $userprofile_name;
    }

    /**
     * @return int
     */
    public function getUserprofileId()
    {
        return $this->userprofile_id;
    }

    /**
     * @param int $userprofile_id
     */
    public function setUserprofileId($userprofile_id)
    {
        $this->userprofile_id = $userprofile_id;
    }

    /**
     * @return ProfileData
     */
    public function getUserprofileProfile()
    {
        return $this->userprofile_profile;
    }

    /**
     * @param ProfileData $userprofile_profile
     */
    public function setUserprofileProfile($userprofile_profile)
    {
        $this->userprofile_profile = $userprofile_profile;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->userprofile_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->userprofile_name;
    }
}