<?php

/*
+---------------------------------------------------------------------------+
| Openads v${RELEASE_MAJOR_MINOR}                                           |
| ============                                                              |
|                                                                           |
| Copyright (c) 2003-2007 Openads Limited                                   |
| For contact details, see: http://www.openads.org/                         |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
$Id:$
*/

/**
 * @package    OpenadsDll
 * @author     Andriy Petlyovanyy <apetlyovanyy@lohika.com>
 *
 * A file to description Agency Information class.
 *
 */

// Include base info class.
require_once MAX_PATH . '/lib/OA/Info.php';

/**
 *  Class with information about agency
 *
 */

class OA_Dll_AgencyInfo extends OA_Info
{
    /**
     * The ID of the agency.
     *
     * @var integer $agencyId
     */
	var $agencyId;

    /**
     * The name of the agency.
     *
     * @var string $agencyName
     */
	var $agencyName;

    /**
     * The name of the contact.
     *
     * @var string $contactName
     */
	var $contactName;

    /**
     * The email address of the contact.
     *
     * @var string $emailAddress
     */
    var $emailAddress;

    /**
     * The username of the contact used to log into OA.
     *
     * @var string $username
     */
    var $username;

    /**
     * The password of the contact used to log into OA.
     *
     * @var string $password
     */
    var $password;
    
    function getFieldsTypes()
    {
        return array(
                    'agencyId' => 'integer',
                    'agencyName' => 'string',
                    'contactName' => 'string',
                    'emailAddress' => 'string',
                    'username' => 'string', 
                    'password' => 'string'
                );
    }
}

?>