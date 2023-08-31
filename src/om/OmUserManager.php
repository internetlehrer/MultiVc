<?php
require_once ("OmGateway.php");
class OmUserManager {
	var $config = array();

	function __construct($cfg) {
		$this->config = $cfg;
	}

	static function getInstance()
    {
        return new self();
    }

	function getUsersByRoomId($roomId) {
        $gateway = new OmGateway($this->config);
        if ($gateway->login()) {
            return $gateway->getUsersByRoomId($roomId);
        } else {
            return -1;
        }
    }

	function update($data) {
		$gateway = new OmGateway($this->config);
		if ($gateway->login()) {
			return $gateway->updateRoom($data);
		} else {
			return -1;
		}
	}

	function delete($roomId) {
		$gateway = new OmGateway($this->config);
		if ($gateway->login()) {
			return $gateway->deleteRoom($roomId);
		} else {
			return -1;
		}
	}

	function get($roomId) {
		$gateway = new OmGateway($this->config);
		if ($gateway->login()) {
			return $gateway->getRoom($roomId);
		} else {
			return -1;
		}
	}
}
