<?php
/*
    PufferPanel - A Minecraft Server Management Panel
    Copyright (c) 2013 Dane Everitt

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see http://www.gnu.org/licenses/.
 */
namespace Auth;

/*
 * TOTP Class
 */
use Otp\Otp;
use Otp\GoogleAuthenticator;
use Base32\Base32;

trait components {

	public function hash($raw){

		return password_hash($raw, PASSWORD_BCRYPT);

	}

	private function password_compare($raw, $hashed){

		if(password_verify($raw, $hashed))
			return true;
		else
			return false;

	}

	public function generate_iv(){

		return base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CBC), MCRYPT_RAND));

	}

	public static function encrypt($raw, $iv, $method = 'AES-256-CBC'){

		return openssl_encrypt($raw, $method, file_get_contents(HASH), false, base64_decode($iv));

	}

	public static function decrypt($encrypted, $iv, $method = 'AES-256-CBC'){

		return openssl_decrypt($encrypted, $method, file_get_contents(HASH), 0, base64_decode($iv));

	}

	/*
	 * Generate RFC 4122 Compliant v4 UUIDs
	 */
	public static function gen_UUID(){

		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff),
						mt_rand(0, 0xffff),
						mt_rand(0, 0x0fff) | 0x4000,
						mt_rand(0, 0x3fff) | 0x8000,
						mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

	}

	public static function keygen($amount){


		$keyset  = "abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ0123456789";

		$randkey = null;
		$maxLength = (strlen($keyset) - 1);

		for ($i=0; $i < $amount; $i++)
			$randkey .= $keyset[mt_rand(0, $maxLength)];

		return str_shuffle($randkey);

	}

	public function getCookie($cookie){

		if(isset($_COOKIE[$cookie])){

			return $_COOKIE[$cookie];

		}else{

			return null;

		}

	}

}

class auth {

	use \Database\database, \Auth\components, \Page\components;

	public function __construct($settings)
		{

			$this->mysql = self::connect();
			$this->settings = $settings;

		}

	public function validateTOTP($key, $secret){

		/*
		 * Check Key
		 */
		$otp = new Otp();

			if($otp->checkTotp(Base32::decode($secret), $key)){

				/*
				 * Prevent TOTP-replay attack
				 */
				//@TODO

				/*
				 * Return Success
				 */
				return true;

			}else
				return false;

	}

	public function verifyPassword($email, $raw){

		$this->get = $this->mysql->prepare("SELECT `password` FROM `users` WHERE `email` = :email");
		$this->get->execute(array(
			':email' => $email
		));

			if($this->get->rowCount() == 1){

				$this->row = $this->get->fetch();
				return $this->password_compare($raw, $this->row['password']);

			}else
				return false;

	}

	public function isLoggedIn($ip, $session, $serverhash = null, $acp = false){

		$this->query = $this->mysql->prepare("SELECT * FROM `users` WHERE `session_ip` = :sessip AND `session_id` = :session");
		$this->query->execute(array(
			':sessip' => $ip,
			':session' => $session
		));

			if($this->query->rowCount() == 1){

				$this->row = $this->query->fetch();

					/*
					 * Accessing Admin Directory
					 */
					if($this->row['root_admin'] != 1 && $acp === true)
						return false;
					else{

						/*
						 * Allow Admins Access to any Server
						 */
						if($this->row['root_admin'] != '1'){

							/*
							 * Validate User is Owner of Server
							 */
							if(!is_null($serverhash)){

								$this->_validateServer = $this->mysql->prepare("SELECT * FROM `servers` WHERE `hash` = :shash AND `owner_id` = :ownerid AND `active` = 1");
								$this->_validateServer->execute(array(
									':shash' => $serverhash,
									':ownerid' => $this->row['id']
								));

									if($this->_validateServer->rowCount() == 1)
										return true;
									else
										return false;

							/*
							 * Just Check if they are Logged In
							 */
							}else{

								return true;

							}

						}else{

							return true;

						}

					}

			}else{

				return false;

			}

	}

}

?>
