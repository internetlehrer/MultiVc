<?php
/**
* Application class for MultiVc repository object.
*
* @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
*
* @version $Id$
*/

use ILIAS\DI\Container;

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");

class ilObjMultiVc extends ilObjectPlugin
{
	const TABLE_LOG_MAX_CONCURRENT = 'rep_robj_xmvc_log_max';

	private $token = 0;
	private $moderated = true;
	private $btnSettings = false;
	private $btnChat = false;
	private $withChat = true;
	private $btnLocationshare = false;
	private $memberBtnFileupload = false;
	private $faExpand = false;
	/** @var string $attendeePwd */
	private $attendeePwd;
	/** @var bool $privateChat */
	private $privateChat;
	/** @var bool $record */
	private $record;
	/** @var bool $camOnlyForModerator */
	private $camOnlyForModerator;
	/** @var bool $guestlink */
	private $guestlink = false;
	/** @var string $moderatorPwd */
	private $moderatorPwd;
	/** @var int $roomId */
	private $roomId;
	/** @var int $connId */
	private $connId;
	/** @var object $option */
	public $option;




	/**
	* Constructor
	*
	* @access	public
	*/
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);

		if( is_numeric($this->getConnId()) ) {
			//$this->setDefaultsByPluginConfig($this->getConnId());
		}
	}

	static public function getInstance() {
		return new self();
	}
	

	/**
	* Get type.
	*/
	final function initType()
	{
		$this->setType("xmvc");
	}
	
	/**
	* Create object
	*/

	function doCreate()
	{
	}
	
	function createRoom($online, int $conn_id)
	{
		global $ilDB;
		$this->setOnline($this->ilIntToBool($online));
		$this->setConnId($conn_id);
		$settings = $this->setDefaultsByPluginConfig($conn_id, true);
		//var_dump($settings) ; exit;
		$a_data=array(
			'id'					=> array('integer', $this->getId()),
			'is_online'				=> array('integer', $this->ilBoolToInt($this->getOnline())),
			'token'					=> array('integer', $this->generate_token()),
			'moderated'				=> array('integer', $this->ilBoolToInt($settings->get_moderatedDefault())),
			'btn_settings'			=> array('integer', $this->ilBoolToInt($settings->get_btnSettingsDefault())),
			'btn_chat'				=> array('integer', $this->ilBoolToInt($settings->get_btnChatDefault())),
			'with_chat'				=> array('integer', $this->ilBoolToInt($settings->get_withChatDefault())),
			'btn_locationshare'		=> array('integer', $this->ilBoolToInt($settings->get_btnLocationshareDefault())),
			'member_btn_fileupload'	=> array('integer', $this->ilBoolToInt($settings->get_memberBtnFileuploadDefault())),
			'fa_expand'				=> array('integer', $this->ilBoolToInt($settings->get_faExpandDefault())),
			'attendeepwd'			=> array('string', $this->generateMembersPwd()),
			'moderatorpwd'			=> array('string', $this->generateMembersPwd()),
			'private_chat'			=> array('integer', (int)$this->isPrivateChat() ),
			'recording'				=> array('integer', (int)$this->isRecord()),
			'cam_only_for_moderator' => array('integer', (int)$this->isCamOnlyForModerator()),
			'conn_id' => array('integer', (int)$conn_id),
			'guestlink' => array('integer', (int)$this->isGuestlink()),
		);
		$ilDB->insert('rep_robj_xmvc_data', $a_data);
	}
	
	/**
	* Read data from db
	*/
	function doRead()
	{
		global $ilDB;
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");

		$result = $ilDB->query("SELECT * FROM rep_robj_xmvc_data WHERE id = ".$ilDB->quote($this->getId(), "integer"));
		while ($record = $ilDB->fetchAssoc($result)) {
			$settings = new ilMultiVcConfig($record["conn_id"]);
			$this->option = $settings->option;
			$this->setPrivateChat( $settings->isPrivateChatDefault() );
			$this->setRecord( $settings->isRecordDefault() );
			$this->setCamOnlyForModerator( $settings->isCamOnlyForModeratorDefault() );

			$this->setOnline($this->ilIntToBool($record["is_online"]));
			$this->set_token($record["token"]);
			$this->set_moderated($this->ilIntToBool($record["moderated"]));
			$this->set_btnSettings($this->ilIntToBool($record["btn_settings"]));
			$this->set_btnChat($this->ilIntToBool($record["btn_chat"]));
			$this->set_withChat($this->ilIntToBool($record["with_chat"]));
			$this->set_btnLocationshare($this->ilIntToBool($record["btn_locationshare"]));
			$this->set_memberBtnFileupload($this->ilIntToBool($record["member_btn_fileupload"]));
			$this->set_faExpand($this->ilIntToBool($record["fa_expand"]));
			$this->setAttendeePwd($record["attendeepwd"]);
			$this->setModeratorPwd($record["moderatorpwd"]);
			$this->setPrivateChat( (bool)$record["private_chat"] );
			$this->setRecord( (bool)$record["recording"] );
			$this->setCamOnlyForModerator( (bool)$record["cam_only_for_moderator"] );
			$this->setRoomId( (int)$record["rmid"] );
			$this->setConnId( (int)$record["conn_id"] );
			$this->setGuestlink( (bool)$record["guestlink"] );
		}
	}

	/**
	* determine Ref-Id of Course with linked SpreedObject
	*/
	function getCourseRefIdForSpreedObjectRefId($refId) {
		global $ilDB;
		$courseId = 0;
		$query = "SELECT parent FROM tree WHERE child=" . $ilDB->quote($refId, "integer");
		$result = $ilDB->query ($query);
		if($dataset = $ilDB->fetchAssoc($result)) {
			$treeParentRefId = $dataset["parent"];
			$query = "SELECT obj_id FROM object_reference WHERE ref_id="  . $ilDB->quote($treeParentRefId, "integer");
			$result = $ilDB->query ($query);
			if($dataset = $ilDB->fetchAssoc($result)) $parentObjId = $dataset["obj_id"];

			if (isset ($parentObjId))
			{
				$query = "SELECT type FROM object_data WHERE obj_id="  . $ilDB->quote($parentObjId, "integer");
				$result = $ilDB->query ($query);
				if($dataset = $ilDB->fetchAssoc($result)) $objectType = $dataset["type"];
				if (isset ($objectType)) {
					if ($objectType == "crs") $courseId = $treeParentRefId;//$parentObjId;
				}
			}
		}
		return $courseId;
	}
	/**
	* Update data
	*/
	function doUpdate()
	{
		global $ilDB;
		$a_data=array(
			// 'id'					=> array('integer', $this->getId()),
			'is_online'				=> array('integer', $this->ilBoolToInt($this->getOnline())),
			'token'					=> array('integer', $this->get_token()),
			'moderated'				=> array('integer', $this->ilBoolToInt($this->get_moderated())),
			'btn_settings'			=> array('integer', $this->ilBoolToInt($this->get_btnSettings())),
			'btn_chat'				=> array('integer', $this->ilBoolToInt($this->get_btnChat())),
			'with_chat'				=> array('integer', $this->ilBoolToInt($this->get_withChat())),
			'btn_locationshare'		=> array('integer', $this->ilBoolToInt($this->get_btnLocationshare())),
			'member_btn_fileupload'	=> array('integer', $this->ilBoolToInt($this->get_memberBtnFileupload())),
			'fa_expand'				=> array('integer', $this->ilBoolToInt($this->get_faExpand())),
			'attendeepwd'			=> ['string', $this->getAttendeePwd()],
			'moderatorpwd'			=> ['string', $this->getModeratorPwd()],
			'private_chat'			=> ['integer', (int)$this->isPrivateChat()],
			'recording'				=> ['integer', (int)$this->isRecord()],
			'cam_only_for_moderator' => ['integer', (int)$this->isCamOnlyForModerator()],
			'conn_id'				  => ['integer', (int)$this->getConnId()],
			'guestlink' => ['integer', (int)$this->isGuestlink()],
		);
		$ilDB->update('rep_robj_xmvc_data', $a_data, array('id' => array('integer', $this->getId())));
	}

	public function updateRoomId($roomId): void
	{
		global $ilDB;

		$this->setRoomId($roomId);
		$data = [
			'rmid' => ['integer', $this->roomId]
		];
		$ilDB->update('rep_robj_xmvc_data', $data, array('id' => array('integer', $this->getId())));

	}

	public function updateConnId(int $connId): void
	{
		global $ilDB;

		$this->setConnId( $connId );
		$data = [
			'conn_id' => ['integer', $this->connId]
		];
		$ilDB->update('rep_robj_xmvc_data', $data, array('id' => array('integer', $this->getId())));
	}

	/**
	* Delete data from db
	*/
	function doDelete()
	{
		global $ilDB;
		$ilDB->manipulate("DELETE FROM rep_robj_xmvc_data WHERE id = ".$ilDB->quote($this->getId(), "integer"));
	}
	
	public function doCloneObject($new_obj, $a_target_id, $a_copy_id = 0)
	{
		global $DIC; /** @var Container $DIC */
		//if( !$DIC->access()->checkAccessOfUser($DIC->user()->getId(), 'write', '', $this->getRefId()) ) {
		//	return;
		//}
		$this->doClone($new_obj, $a_target_id, $a_copy_id);
	}

	/**
	 * Do Cloning
	 * @param $new_obj
	 * @param $a_target_id
	 * @param $a_copy_id
	 */
	function doClone(ilObjMultiVc $new_obj, $a_target_id, $a_copy_id)
	{
		global $ilDB;
		$a_data=array(
			'id'					=> array('integer', $new_obj->getId()), // $a_target_id
			'is_online'				=> array('integer', $this->ilBoolToInt($this->getOnline())),
			'token'					=> array('integer', $this->generate_token()),
			'moderated'				=> array('integer', $this->ilBoolToInt($this->get_moderated())),
			'btn_settings'			=> array('integer', $this->ilBoolToInt($this->get_btnSettings())),
			'btn_chat'				=> array('integer', $this->ilBoolToInt($this->get_btnChat())),
			'with_chat'				=> array('integer', $this->ilBoolToInt($this->get_withChat())),
			'btn_locationshare'		=> array('integer', $this->ilBoolToInt($this->get_btnLocationshare())),
			'member_btn_fileupload'	=> array('integer', $this->ilBoolToInt($this->get_memberBtnFileupload())),
			'fa_expand'				=> array('integer', $this->ilBoolToInt($this->get_faExpand())),
			'attendeepwd'			=> ['string', $this->generateMembersPwd()],
			'moderatorpwd'			=> ['string', $this->generateMembersPwd()],
			'private_chat'			=> ['integer', (int)$this->isPrivateChat()],
			'recording'				=> ['integer', (int)$this->isRecord()],
			'cam_only_for_moderator' => ['integer', (int)$this->isCamOnlyForModerator()],
			'conn_id'				  => ['integer', (int)$this->getConnId()],
			'guestlink' => ['integer', (int)$this->isGuestlink()],

			//'more_options'			=> ['string', json_encode($this->option)],
		);
		$ilDB->insert('rep_robj_xmvc_data', $a_data);
		// $new_obj->createRoom($this->getOnline());
		// $new_obj->doUpdate();
	}
	
	//
	// Set/Get Methods for our MultiVc properties
	//

	/**
	* Set online
	*
	* @param	boolean		online
	*/
	function setOnline($a_val)
	{
		$this->online = $a_val;
	}
	
	/**
	* Get online
	*
	* @return	boolean		online
	*/
	function getOnline()
	{
		return $this->online;
	}

	/**
	 * @return bool
	 */
	public function get_moderated() {
		return $this->moderated;
	}
	public function set_moderated($a_moderated) {
		$this->moderated = $a_moderated;
	}

	public function get_btnSettings() {
		return $this->btnSettings;
	}
	public function set_btnSettings($a_btnSettings) {
		$this->btnSettings = $a_btnSettings;
	}

	public function get_btnChat() {
		return $this->btnChat;
	}
	public function set_btnChat($a_btnChat) {
		$this->btnChat = $a_btnChat;
	}

	public function get_withChat() {
		return $this->withChat;
	}
	public function set_withChat($a_withChat) {
		$this->withChat = $a_withChat;
	}

	public function get_btnLocationshare() {
		return $this->btnLocationshare;
	}
	public function set_btnLocationshare($a_btnLocationshare) {
		$this->btnLocationshare = $a_btnLocationshare;
	}

	public function get_memberBtnFileupload() {
		return $this->memberBtnFileupload;
	}
	public function set_memberBtnFileupload($a_memberBtnFileupload) {
		$this->memberBtnFileupload = $a_memberBtnFileupload;
	}

	public function get_faExpand() {
		return $this->faExpand;
	}
	public function set_faExpand($a_faExpand) {
		$this->faExpand = $a_faExpand;
	}

	function set_token($a_val){
		$this->token = $a_val;
	}
	function get_token(){
		return $this->token;
	}
	function generate_token(){
		$this->token = mt_rand(1000000000,9999999999);
	}

	
	function ilBoolToInt($a_val) {
		if ($a_val == true) return 1;
		return 0;
	}
	function ilIntToBool($a_val) {
		if ($a_val == 1) return true;
		return false;
	}

	/**
	 * @return string
	 */
	public function getAttendeePwd(): ?string
	{
		return $this->attendeePwd;
	}

	/**
	 * @param string $attendeePwd
	 */
	public function setAttendeePwd(?string $attendeePwd): void
	{
		$this->attendeePwd = $attendeePwd;
	}

	/**
	 * @return string
	 */
	public function getModeratorPwd(): ?string
	{
		return $this->moderatorPwd;
	}

	/**
	 * @param string $moderatorPwd
	 */
	public function setModeratorPwd(?string $moderatorPwd): void
	{
		$this->moderatorPwd = $moderatorPwd;
	}

	/**
	 * @return bool
	 */
	public function isPrivateChat(): bool
	{
		return $this->privateChat;
	}

	/**
	 * @param bool $privateChat
	 */
	public function setPrivateChat(bool $privateChat): void
	{
		$this->privateChat = $privateChat;
	}

	/**
	 * @return bool
	 */
	public function isRecord(): bool
	{
		return $this->record;
	}

	/**
	 * @return bool
	 */
	public function isRecordingAllowed(): bool
	{
		return $this->record;
	}

	/**
	 * @param bool $record
	 */
	public function setRecord(bool $record): void
	{
		$this->record = $record;
	}

	/**
	 * @return bool
	 */
	public function isCamOnlyForModerator(): bool
	{
		return $this->camOnlyForModerator;
	}

	/**
	 * @param bool $camOnlyForModerator
	 */
	public function setCamOnlyForModerator(bool $camOnlyForModerator): void
	{
		$this->camOnlyForModerator = $camOnlyForModerator;
	}

	/**
	 * @return bool
	 */
	public function isGuestlink(): bool
	{
		return $this->guestlink;
	}

	/**
	 * @param bool $guestlink
	 */
	public function setGuestlink(bool $guestlink): void
	{
		$this->guestlink = $guestlink;
	}



	public function getMaxConcurrent(string $order = 'desc', int $limit = 100): array {
		global $DIC; /** @var Container $DIC */
		$sql = "SELECT * FROM " . self::TABLE_LOG_MAX_CONCURRENT
		. " WHERE 1"
		. " ORDER BY year, " . $DIC->database()->quote($order)
		//. " LIMIT " . $DIC->database()->quote($limit);
		;
		$query = $DIC->database()->query($sql);
		$data = [];
		while( $row = $DIC->database()->fetchAssoc($query) ) {
			$data[] = $row;
		}
		return $data;
	}

	private function getCurrentMaxConcurrent(): array {
		global $DIC; /** @var Container $DIC */
		$defEntry = [
			'year' => date("Y"),
			'month' => date("m"),
			'day' => date("d"),
			'hour' => date("H"),
			'max_meetings' => 0,
			'max_users' => 0,
			'entries' => 0
		];

		$values = [
			$defEntry['year'],
			$defEntry['month'],
			$defEntry['day'],
			$defEntry['hour'],
		];

		$types = ['int','int','int','int'];

		$sql = "SELECT max_meetings, max_users FROM " . self::TABLE_LOG_MAX_CONCURRENT .
			" WHERE year = %s AND month = %s AND day = %s AND hour = %s";
		$query = $DIC->database()->queryF($sql, $types, $values);
		$entry = $DIC->database()->fetchAssoc($query);

		return null === $entry ? $defEntry : array_merge($defEntry, $entry, ['entries' => 1]);
	}

	public function saveMaxConcurrent(int $meetings, int $users): void {
		global $DIC; /** @var Container $DIC */

		$currentMax = $this->getCurrentMaxConcurrent();
		$currentMax['max_meetings'] = $currentMax['max_meetings'] > $meetings ? $currentMax['max_meetings'] : $meetings;
		$currentMax['max_users'] = $currentMax['max_users'] > $users ? $currentMax['max_users'] : $users;

		if( !(bool)$currentMax['entries'] ) {
			// insert
			$DIC->database()->insert(
				self::TABLE_LOG_MAX_CONCURRENT,
				array(
					'year'	=> ['integer', $currentMax['year']],
					'month'	=> ['integer', $currentMax['month']],
					'day'	=> ['integer', $currentMax['day']],
					'hour'	=> ['integer', $currentMax['hour']],
					'max_meetings'	=> ['integer', $currentMax['max_meetings']],
					'max_users'	=> ['integer', $currentMax['max_users']],
				)
			);
		} else {
			// update
			$DIC->database()->update(
				self::TABLE_LOG_MAX_CONCURRENT,
				array(
					'max_meetings'	=> ['integer', $currentMax['max_meetings']],
					'max_users'	=> ['integer', $currentMax['max_users']],
				),
				array(
					'year'	=> ['integer', $currentMax['year']],
					'month'	=> ['integer', $currentMax['month']],
					'day'	=> ['integer', $currentMax['day']],
					'hour'	=> ['integer', $currentMax['hour']],
				)
			);
		}
		//var_dump([$currentMax, $this->getCurrentMaxConcurrent()]); exit;
	}

	/**
	 * @param bool|string $salt Default false. Call fn with a string as salt
	 * @param int $length Default 16
	 * @return string Random Password String
	 */
	public function generateMembersPwd(?bool $salt = false, int $length = 16): string {
		$chars = 'asdfghlkjqwerpoiutzxmcvbnASDFLKJGHQWERPOIUTZXCVBMN';
		$digits = '1234567890';
		$salt = (bool)strlen($salt) === false ? $digits : $salt;
		$haystack = str_shuffle(str_repeat($digits . $chars . $salt, 3));
		return substr($haystack, rand(0, strlen($haystack) -1 - $length), $length);
	}

	private function renewPasswordsBBBVCR(int $id)
	{
		global $DIC; /** @var Container $DIC */

		$db = $DIC->database();

		$update['where'] = ['id' => ['integer', $id]];
		$update['values'] = [
			'attendeepwd'			=> ['string', $this->generateMembersPwd()],
			'moderatorpwd'			=> ['string', $this->generateMembersPwd()]
		];

		$db->update('rep_robj_xmvc_data', $update['values'], $update['where']);
	}

	public function fillEmptyPasswordsBBBVCR()
	{
		global $DIC; /** @var Container $DIC */

		$db = $DIC->database();

		$query = "SELECT id FROM rep_robj_xmvc_data WHERE moderatorpwd IS NULL OR attendeepwd IS NULL";
		$result = $db->query($query);
		$update = [];
		$i = 0;
		while( $row = $db->fetchAssoc($result) ) {
			$update[$i]['where'] = ['id' => ['integer', $row['id']]];
			$update[$i]['values'] = [
				'attendeepwd'			=> ['string', $this->generateMembersPwd()],
				'moderatorpwd'			=> ['string', $this->generateMembersPwd()]
			];
			$i++;
		}

		if( sizeof($update) > 0 ) {
			foreach( $update as $item ) {
				$db->update('rep_robj_xmvc_data', $item['values'], $item['where']);
			}
		}
	}

	/**
	 * @return int
	 */
	public function getRoomId(): int
	{
		return $this->roomId;
	}

	/**
	 * @param int $roomId
	 */
	public function setRoomId(int $roomId): void
	{
		$this->roomId = $roomId;
	}

	/**
	 * @return int
	 */
	public function getConnId(): ?int
	{
		return $this->connId;
	}

	/**
	 * @param int $connId
	 */
	public function setConnId(int $connId): void
	{
		$this->connId = $connId;
	}


	private function setDefaultsByPluginConfig(?int $connId, bool $getObject = false): ?ilMultiVcConfig {
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/MultiVc/classes/class.ilMultiVcConfig.php");
		$settings = new ilMultiVcConfig($connId);
		$this->option = $settings->option;
		$this->setPrivateChat( $settings->isPrivateChatDefault() );
		$this->setRecord( $settings->isRecordDefault() );
		$this->setCamOnlyForModerator( $settings->isCamOnlyForModeratorDefault() );
		if( $getObject ) {
			return $settings;
		}
	}

	public static function getMultiVcConnTitleAndTypeByObjId(int $objId)
	{
		global $DIC; /** @var Container $DIC */
		$db = $DIC->database();

		$query = "SELECT rep_robj_xmvc_conn.title title, rep_robj_xmvc_conn.showcontent type FROM rep_robj_xmvc_data, rep_robj_xmvc_conn WHERE rep_robj_xmvc_conn.id = rep_robj_xmvc_data.conn_id AND rep_robj_xmvc_data.id = " . $db->quote($objId, 'integer');
		$result = $db->query($query);
		return $db->fetchObject($result);
	}



}
?>
