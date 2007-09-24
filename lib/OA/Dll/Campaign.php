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
 * A file to description Dll Campaign class.
 *
 */

// Require the XMLRPC classes
require_once MAX_PATH . '/lib/OA/Dll.php';
require_once MAX_PATH . '/lib/OA/Dll/CampaignInfo.php';
require_once MAX_PATH . '/lib/OA/Dal/Statistics/Campaign.php';


/**
 * Campaign Dll class
 *
 */

class OA_Dll_Campaign extends OA_Dll
{
    /**
     * Init campaign info from data array
     *
     * @access private
     *
     * @param OA_Dll_CampaignInfo &$oCampaign
     * @param array $campaignData
     *
     * @return boolean
     */
    function _setCampaignDataFromArray(&$oCampaign, $campaignData)
    {
        $campaignData['campaignId']   = $campaignData['campaignid'];
        $campaignData['campaignName'] = $campaignData['campaignname'];
        $campaignData['advertiserId'] = $campaignData['clientid'];
        $campaignData['startDate']    = $campaignData['activate'];
        $campaignData['endDate']      = $campaignData['expire'];
        $campaignData['impressions']  = $campaignData['views'];

        $oCampaign->readDataFromArray($campaignData);
        return  true;
    }

    /**
     * Method would perform data validation (e.g. email is an email)
     * and where necessary would connect to the DAL to obtain information
     * required to perform other business validations (e.g. username
     * must be unique across all relevant tables).
     *
     * @access private
     *
     * @param OA_Dll_CampaignInfo $oCampaign
     *
     * @return boolean
     *
     */
    function _validate(&$oCampaign)
    {
        if (isset($oCampaign->campaignId)) {
            // Modify Campaign
            if (!$this->checkStructureRequiredIntegerField($oCampaign, 'campaignId') ||
                !$this->checkIdExistence('campaigns', $oCampaign->campaignId)) {
                return false;
            }

            if (!$this->checkStructureNotRequiredIntegerField($oCampaign, 'advertiserId')) {
                return false;
            }

            if (isset($oCampaign->advertiserId) &&
                !$this->checkIdExistence('clients', $oCampaign->advertiserId)) {
                return false;
            }
        } else {
            // Add Campaign
            if (!$this->checkStructureRequiredIntegerField($oCampaign, 'advertiserId') ||
                !$this->checkIdExistence('clients', $oCampaign->advertiserId)) {
                return false;
            }
        }

        // If we have 2 dates we need to check dates order
        if (is_object($oCampaign->startDate) && is_object($oCampaign->endDate)) {
            if (!$this->checkDateOrder($oCampaign->startDate, $oCampaign->endDate)) {
                return false;
            }
        }

        // Check priority and weight.
        // High priority is between 1 and 10.
        if (isset($oCampaign->priority) &&
            (($oCampaign->priority >= 1) && ($oCampaign->priority <= 10)) &&
            isset($oCampaign->weight) && ($oCampaign->weight > 0)) {

            $this->raiseError('The weight could not be greater than zero for'.
                                ' high or medium priority campaigns');
            return false;
        }

        if (!$this->checkStructureNotRequiredStringField($oCampaign, 'campaignName', 255) ||
            !$this->checkStructureNotRequiredIntegerField($oCampaign, 'impressions') ||
            !$this->checkStructureNotRequiredIntegerField($oCampaign, 'clicks') ||
            !$this->checkStructureNotRequiredIntegerField($oCampaign, 'priority') ||
            !$this->checkStructureNotRequiredIntegerField($oCampaign, 'weight')) {

            return false;
        } else {
            return true;
        }

    }

    /**
     * Method would perform data validation for statistics methods(campaignId,
     * date).
     *
     * @access private
     *
     * @param integer  $campaignId
     * @param date     $oStartDate
     * @param date     $oEndDate
     *
     * @return boolean
     *
     */
    function _validateForStatistics($campaignId, $oStartDate, $oEndDate)
    {
        if (!$this->checkIdExistence('campaigns', $campaignId) ||
            !$this->checkDateOrder($oStartDate, $oEndDate)) {

            return false;
        } else {
            return true;
        }
    }

    /**
     * Calls method for checking permissions from Dll class.
     *
     * @param integer $campaignId  Campaign ID
     *
     * @return boolean  False in access forbidden and true in other case.
     */
    function checkStatisticsPermissions($campaignId)
    {
       if (!$this->checkPermissions(phpAds_Admin + phpAds_Agency +
            phpAds_Client, 'campaigns', $campaignId)) {

            return false;
        } else {
            return true;
        }
    }

    /**
     * This method modifies an existing campaign. All fields which are
     * undefined (e.g. permissions) are not changed from the state they
     * were before modification. Any fields defined below
     * that are NULL are unchanged.<br>
     * (Add would be triggered by modify where primary ID is null)
     *
     * @access public
     *
     * @param OA_Dll_CampaignInfo &$oCampaign
     *
     * @return boolean  True if the operation was successful
     *
     */
    function modify(&$oCampaign)
    {
        if (!isset($oCampaign->campaignId)) {
            // Add
            $oCampaign->setDefaultForAdd();
            if (!$this->checkPermissions(phpAds_Admin + phpAds_Agency,
                'clients', $oCampaign->advertiserId)) {

                return false;
            }
        } else {
            // Edit
            if (!$this->checkPermissions(phpAds_Admin + phpAds_Agency,
                'campaigns', $oCampaign->campaignId)) {

                return false;
            }
        }

        $oStartDate    = $oCampaign->startDate;
        $oEndDate      = $oCampaign->endDate;
        $campaignData  =  (array) $oCampaign;

        $campaignData['campaignid']   = $oCampaign->campaignId;
        $campaignData['campaignname'] = $oCampaign->campaignName;
        $campaignData['clientid']     = $oCampaign->advertiserId;
        if (is_object($oStartDate)) {
            $campaignData['activate'] = $oStartDate->format("%Y-%m-%d");
        }
        if (is_object($oEndDate)) {
            $campaignData['expire']   = $oEndDate->format("%Y-%m-%d");
        }

        $campaignData['views']        = $oCampaign->impressions;

        if ($this->_validate($oCampaign)) {
            $doCampaign = OA_Dal::factoryDO('campaigns');
            if (!isset($oCampaign->campaignId)) {
                $doCampaign->setFrom($campaignData);
                $oCampaign->campaignId = $doCampaign->insert();
            } else {
                $doCampaign->get($campaignData['campaignid']);
                $doCampaign->setFrom($campaignData);
                $doCampaign->update();
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * This method deletes an existing campaign.
     *
     * @access public
     *
     * @param integer $campaignId  The ID of the campaign to delete
     *
     * @return boolean  True if the operation was successful
     *
     */
    function delete($campaignId)
    {
        if (!$this->checkPermissions(phpAds_Admin + phpAds_Agency,
            'campaigns', $campaignId)) {

            return false;
        }

        if (!$this->checkIdExistence('campaigns', $campaignId)) {
            return false;
        }

        $doCampaign = OA_Dal::factoryDO('campaigns');
        $doCampaign->campaignid = $campaignId;
        $result = $doCampaign->delete();

        if ($result) {
            return true;
        } else {
        	$this->raiseError('Unknown campaignId Error');
            return false;
        }
    }

    /**
     * Returns campaign information by campaign id
     *
     * @access public
     *
     * @param int $campaignId
     * @param OA_Dll_CampaignInfo &$oCampaign
     *
     * @return boolean
     */
    function getCampaign($campaignId, &$oCampaign)
    {
        if ($this->checkIdExistence('campaigns', $campaignId)) {
            if (!$this->checkPermissions(null, 'campaigns', $campaignId)) {
                return false;
            }
            $doCampaign = OA_Dal::factoryDO('campaigns');
            $doCampaign->get($campaignId);
            $campaignData = $doCampaign->toArray();

            $oCampaign = new OA_Dll_CampaignInfo();

            $this->_setCampaignDataFromArray($oCampaign, $campaignData);
            return true;

        } else {

            $this->raiseError('Unknown campaignId Error');
            return false;
        }
    }

    /**
     * Returns list of campaigns by campaign id
     *
     * @access public
     *
     * @param int $advertiserId
     * @param array &$aCampaignList
     *
     * @return boolean
     */
    function getCampaignListByAdvertiserId($advertiserId, &$aCampaignList)
    {
        $aCampaignList = array();

        if (!$this->checkIdExistence('clients', $advertiserId)) {
                return false;
        }

        if (!$this->checkPermissions(null, 'clients', $advertiserId)) {
            return false;
        }

        $doCampaign = OA_Dal::factoryDO('campaigns');
        $doCampaign->clientid = $advertiserId;
        $doCampaign->find();

        while ($doCampaign->fetch()) {
            $campaignData = $doCampaign->toArray();

            $oCampaign = new OA_Dll_CampaignInfo();
            $this->_setCampaignDataFromArray($oCampaign, $campaignData);

            $aCampaignList[] = $oCampaign;
        }
        return true;
    }

    /**
    * This method returns statistics for a given campaign, broken down by day.
    *
    * @access public
    *
    * @param integer $campaignId The ID of the campaign to view statistics
    * @param date $oStartDate The date from which to get statistics (inclusive)
    * @param date $oEndDate The date to which to get statistics (inclusive)
    * @param array &$rsStatisticsData Parameter for returned data from function
    * <ul>
    *   <li><b>day date</b>  The day
    *   <li><b>requests integer</b>  The number of requests for the day
    *   <li><b>impressions integer</b>  The number of impressions for the day
    *   <li><b>clicks integer</b>  The number of clicks for the day
    *   <li><b>revenue decimal</b>  The revenue earned for the day
    * </ul>
    *
    * @return boolean  True if the operation was successful and false on error.
    *
    */
    function getCampaignDailyStatistics($campaignId, $oStartDate, $oEndDate, &$rsStatisticsData)
    {
        if (!$this->checkStatisticsPermissions($campaignId)) {
            return false;
        }

        if ($this->_validateForStatistics($campaignId, $oStartDate, $oEndDate)) {
            $dalCampaign = new OA_Dal_Statistics_Campaign;
            $rsStatisticsData = $dalCampaign->getCampaignDailyStatistics($campaignId,
                $oStartDate, $oEndDate);

            return true;
        } else {
            return false;
        }
    }

    /**
    * This method returns statistics for a given campaign, broken down by banner.
    *
    * @access public
    *
    * @param integer $campaignId The ID of the campaign to view statistics
    * @param date $oStartDate The date from which to get statistics (inclusive)
    * @param date $oEndDate The date to which to get statistics (inclusive)
    * @param array &$rsStatisticsData Parameter for returned data from function
    * <ul>
    *   <li><b>advertiserID integer</b> The ID of the advertiser
    *   <li><b>advertiserName string (255)</b> The name of the advertiser
    *   <li><b>campaignID integer</b> The ID of the campaign
    *   <li><b>campaignName string (255)</b> The name of the campaign
    *   <li><b>bannerID integer</b> The ID of the banner
    *   <li><b>bannerName string (255)</b> The name of the banner
    *   <li><b>requests integer</b> The number of requests for the day
    *   <li><b>impressions integer</b> The number of impressions for the day
    *   <li><b>clicks integer</b> The number of clicks for the day
    *   <li><b>revenue decimal</b> The revenue earned for the day
    * </ul>
    *
    * @return boolean  True if the operation was successful and false on error.
    *
    */
    function getCampaignBannerStatistics($campaignId, $oStartDate, $oEndDate, &$rsStatisticsData)
    {
        if (!$this->checkStatisticsPermissions($campaignId)) {
            return false;
        }

        if ($this->_validateForStatistics($campaignId, $oStartDate, $oEndDate)) {
            $dalCampaign = new OA_Dal_Statistics_Campaign;
            $rsStatisticsData = $dalCampaign->getCampaignBannerStatistics($campaignId,
                $oStartDate, $oEndDate);

            return true;
        } else {
            return false;
        }
    }

    /**
    * This method returns statistics for a given campaign, broken down by publisher.
    *
    * @access public
    *
    * @param integer $campaignId The ID of the campaign to view statistics
    * @param date $oStartDate The date from which to get statistics (inclusive)
    * @param date $oEndDate The date to which to get statistics (inclusive)
    * @param array &$rsStatisticsData Parameter for returned data from function
    * <ul>
    *   <li><b>publisherID integer</b> The ID of the publisher
    *   <li><b>publisherName string (255)</b> The name of the publisher
    *   <li><b>requests integer</b> The number of requests for the day
    *   <li><b>impressions integer</b> The number of impressions for the day
    *   <li><b>clicks integer</b> The number of clicks for the day
    *   <li><b>revenue decimal</b> The revenue earned for the day
    * </ul>
    *
    * @return boolean  True if the operation was successful and false on error.
    *
    */
    function getCampaignPublisherStatistics($campaignId, $oStartDate, $oEndDate, &$rsStatisticsData)
    {
        if (!$this->checkStatisticsPermissions($campaignId)) {
            return false;
        }

        if ($this->_validateForStatistics($campaignId, $oStartDate, $oEndDate)) {
            $dalCampaign = new OA_Dal_Statistics_Campaign;
            $rsStatisticsData = $dalCampaign->getCampaignpublisherStatistics($campaignId,
                $oStartDate, $oEndDate);

            return true;
        } else {
            return false;
        }



    }

    /**
    * This method returns statistics for a given campaign, broken down by zone.
    *
    * @access public
    *
    * @param integer $campaignId The ID of the campaign to view statistics
    * @param date $oStartDate The date from which to get statistics (inclusive)
    * @param date $oEndDate The date to which to get statistics (inclusive)
    * @param array &$rsStatisticsData Parameter for returned data from function
    * <ul>
    *   <li><b>publisherID integer</b> The ID of the publisher
    *   <li><b>publisherName string (255)</b> The name of the publisher
    *   <li><b>zoneID integer</b> The ID of the zone
    *   <li><b>zoneName string (255)</b> The name of the zone
    *   <li><b>requests integer</b> The number of requests for the day
    *   <li><b>impressions integer</b> The number of impressions for the day
    *   <li><b>clicks integer</b> The number of clicks for the day
    *   <li><b>revenue decimal</b> The revenue earned for the day
    * </ul>
    *
    * @return boolean  True if the operation was successful and false on error.
    *
    */
    function getCampaignZoneStatistics($campaignId, $oStartDate, $oEndDate, &$rsStatisticsData)
    {
        if (!$this->checkStatisticsPermissions($campaignId)) {
            return false;
        }

        if ($this->_validateForStatistics($campaignId, $oStartDate, $oEndDate)) {
            $dalCampaign = new OA_Dal_Statistics_Campaign;
            $rsStatisticsData = $dalCampaign->getCampaignZoneStatistics($campaignId,
                $oStartDate, $oEndDate);

            return true;
        } else {
            return false;
        }
    }

}

?>