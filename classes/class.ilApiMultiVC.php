<?php


use ILIAS\DI\Container;
require_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");

abstract class ilApiMultiVC
{
    const PLUGIN_PATH = './Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc';
    const INI_FILENAME = 'plugin.ini';

    /** @var Container $DIC */
    protected $dic;

    /** @var ilMultiVcConfig $settings */
    protected $settings;

    /** @var ilObjMultiVc $object */
    protected $object;

    /** @var array $pluginIniSet */
    protected $pluginIniSet = [];

    /** @var bool|ilObject $parentObj */
    protected $parentObj;

    /** @var bool|ilObjCourse $course */
    protected $course;

    /** @var bool|ilObjGroup $group */
    protected $group;

    /** @var bool|ilObjCategory $category */
    protected $category;

    /** @var ilObjSession $ilObjSession */
    protected $ilObjSession;

    /** @var string $meetingId */
    protected $meetingId = 0;

    /** @var bool $moderatedMeeting */
    protected $moderatedMeeting;

    /** @var bool $meetingStartable */
    protected $meetingStartable;

    /** @var bool $meetingRecordable */
    protected $meetingRecordable = false;

    /** @var string $userRole */
    protected $userRole;


    ####################################################################################################################
    #### CONSTRUCTOR
    ####################################################################################################################

    function __construct(ilObjMultiVcGUI $a_parent)
    {
        global $DIC; /** @var Container $DIC */
        $this->dic = $DIC;

        $this->settings = ilMultiVcConfig::getInstance($a_parent->object->getConnId());
        $this->object = $a_parent->object;

        $this->setPluginIniSet();

        $this->moderatedMeeting = $this->object->get_moderated();

        $this->setMeetingId();

        $this->setUserRole();

        $this->setMeetingRecordable((bool)$this->object->isRecordingAllowed());
        $this->setRecordingOnlyForModeratedMeeting();

    }


    ####################################################################################################################
    #### GENERAL OBJECT SETTINGS
    ####################################################################################################################

    /**
     * Get the setting entries of plugin.ini and set them as an assoc array
     */
    private function setPluginIniSet(): void
    {
        global $DIC; /** @var \DI\Container $DIC */
        $iniPathFile = self::PLUGIN_PATH . '/' . self::INI_FILENAME;
        if( file_exists($iniPathFile) ) {
            $iniContent = file_get_contents($iniPathFile);
            foreach( explode("\n", $iniContent) as $line ) {
                if( substr_count($line, '=') ) {
                    list($key, $value) = explode('=', $line);
                    $this->pluginIniSet[trim($key)] = trim($value);
                }
            }
        }
    }

    /**
     * @return bool
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    protected function isInCourseOrGroup(): bool
    {
        if( !$this->parentObj )
        {
            $this->setParentObj();
        }

        if( !$this->category )
        {
            return true;
        }

        return false;
    }

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    protected function setParentObj(): void
    {
        global $DIC; /** @var Container $DIC */

        $path = array_reverse($DIC->repositoryTree()->getPathFull($this->object->getRefId()));
        $keys = array_keys($path);
        $parent = $path[$keys[1]];

        $this->parentObj = ilObjectFactory::getInstanceByRefId($parent['ref_id']);
        switch( true )
        {
            case 'crs' === $parent['type'] && !$this->course:
                $this->course = $this->parentObj;
                break;
            case 'grp' === $parent['type'] && !$this->group:
                $this->group = $this->parentObj;
                break;
            case 'cat' === $parent['type'] && !$this->category:
                $this->category = $this->parentObj;
                break;
        }

        // check for ilObjSession
        if( false !== array_search($parent['type'], ['crs', 'grp']) ) {
            $events = ilEventItems::getEventsForItemOrderedByStartingTime($this->object->getRefId());
            if( (bool)sizeof($events) ) {
                $now = date('U');
                //var_dump($now);
                foreach( $events as $eventId => $eventStart ) {
                    if( !$this->ilObjSession ) {
                        /** @var ilObjSession $tmpSessObj */
                        $tmpSessObj = ilObjectFactory::getInstanceByObjId($eventId);

                        $dTplId = ilDidacticTemplateObjSettings::lookupTemplateId($tmpSessObj->getRefId());

                        if( !(bool)$dTplId && $now >= $eventStart ) {
                            $event = ilSessionAppointment::_lookupAppointment($eventId);
                            $end = (bool)$event['fullday']
                                ? $eventStart + 60 * 60 *24
                                : $event['end'];
                            if( $now < $end ) {
                                $this->ilObjSession = $tmpSessObj; // ilObjectFactory::getInstanceByObjId($eventId);
                            }
                        }
                    }
                }
            }

        }

        //echo $this->ilObjSession->; exit;
    }

    protected function setMeetingId(): void
    {

        $rawMeetingId = $this->dic->settings()->get('inst_id',0) . $this->object->getId();

        if ($this->settings->get_objIdsSpecial() != '') {
            $ArObjIdsSpecial = [];
            $rawIds = explode(",", $this->settings->get_objIdsSpecial());
            foreach ($rawIds as $id) {
                $id = trim($id);
                if ( is_numeric($id) ) {
                    array_push($ArObjIdsSpecial, $id);
                }
            }
            if ( in_array($this->object->getId(), $ArObjIdsSpecial) ) {
                $rawMeetingId .= 'r' . $this->object->getRefId();
            }
        }
        $this->meetingId = md5($rawMeetingId);
    }


    ####################################################################################################################
    #### USER SETTINGS
    ####################################################################################################################

    /**
     * @return bool
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    protected function isAdminOrTutor(): bool
    {
        global $DIC; /** @var \DI\Container $DIC */

        if( $this->isInCourseOrGroup() )
        {
            $userLiaRoles = $DIC->rbac()->review()->assignedRoles($DIC->user()->getId());
            if( !!$this->course )
            {
                $found = array_search($this->course->getDefaultAdminRole(), $userLiaRoles);
                $found = false !== $found ? true : array_search($this->course->getDefaultTutorRole(), $userLiaRoles);
                return false !== $found;
            }
            if( !!$this->group )
            {
                $found = array_search($this->group->getDefaultAdminRole(), $userLiaRoles);
                return false !== $found;
            }
        }
        return false;
    }

    /**
     * @throws ilDatabaseException
     * @throws ilObjectNotFoundException
     */
    protected function setUserRole(): void
    {
        global $DIC; /** @var \DI\Container $DIC */

        switch (true)
        {
            case $this->isInCourseOrGroup() && $this->isAdminOrTutor():
            case $this->isInCourseOrGroup() && $DIC->access()->checkAccessOfUser($DIC->user()->getId(), 'write', 'showContent', $this->object->getRefId()):
            case !$this->isInCourseOrGroup() && $DIC->access()->checkAccessOfUser($DIC->user()->getId(), 'write', 'showContent', $this->object->getRefId()):
            case !$this->isModeratedMeeting():
                $this->userRole = 'moderator';
                break;
            default:
                $this->userRole = 'attendee';
        }
    }



    #### MEETING SETTINGS

    protected function setRecordingOnlyForModeratedMeeting(): void {
        if( (bool)$this->object->isRecordingAllowed() &&
            $this->settings->isRecordOnlyForModeratedRoomsDefault() &&
            !$this->isModeratedMeeting()
        ) {
            $this->setMeetingRecordable(false);
        }
    }

    /**
     * @param bool $meetingRecordable
     */
    protected function setMeetingRecordable(bool $meetingRecordable): void
    {
        $this->meetingRecordable = $meetingRecordable;
    }



    ####################################################################################################################
    #### PUBLIC GETTERS & SETTERS
    ####################################################################################################################

    /**
     * @param string $value
     * @return string|null
     */
    public function getPluginIniSet(string $value = 'max_concurrent_users'): ?string
    {
        return isset($this->pluginIniSet[$value]) ? $this->pluginIniSet[$value] : null;
    }

    /**
     * @return string
     */
    public function getMeetingId(): string
    {
        return $this->meetingId;
    }

    /**
     * @return bool
     */
    public function isModeratedMeeting(): bool
    {
        return $this->moderatedMeeting;
    }


    /**
     * @return bool
     */
    public function isUserModerator(): bool
    {
        return $this->userRole === 'moderator';
    }

    /**
     * @return bool
     */
    public function isUserAdmin(): bool {
        global $DIC; /** @var \DI\Container $DIC */
        $userLiaRoles = $this->dic->rbac()->review()->assignedRoles($DIC->user()->getId());
        if( null !== $this->course ) {
            $found = array_search($this->course->getDefaultAdminRole(), $userLiaRoles);
        } else {
            $found = $this->dic->access()->checkAccessOfUser($this->dic->user()->getId(), 'write', 'showContent', $this->object->getRefId());
        }
        return false !== $found;
    }

    /**
     * @return bool
     */
    public function isValidAppointmentUser(): bool
    {
        return !$this->ilObjSession ||
            (
                !!$this->ilObjSession &&
                ilEventParticipants::_isRegistered($this->dic->user()->getId(), $this->ilObjSession->getId())
            );
    }

    /**
     * @return bool
     */
    public function isMeetingRecordable(): bool
    {
        return $this->meetingRecordable;
    }



}