<?php
/**
 * Copyright (c) 2011 Bart Visscher bartv@thisnet.nl
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts;

/**
 * This class manages our app actions
 */
App::$l10n = \OC_L10N::get('contacts');

class App {
	/*
	 * @brief language object for calendar app
	 */

	public static $l10n;
	/*
	 * @brief categories of the user
	 */
	public static $categories = null;

	/**
	 * Properties there can be more than one of.
	 */
	public static $multi_properties = array('EMAIL', 'TEL', 'IMPP', 'ADR', 'URL');

	const THUMBNAIL_PREFIX = 'contact-thumbnail-';
	const THUMBNAIL_SIZE = 28;

	/**
	 * @brief Gets the VCard as an OC_VObject
	 * @returns The card or null if the card could not be parsed.
	 */
	public static function getContactVCard($id) {
		$card = null;
		try {
			$card = VCard::find($id);
		} catch(Exception $e) {
			return null;
		}

		$vcard = \OC_VObject::parse($card['carddata']);
		if (!is_null($vcard) && !isset($vcard->REV)) {
			$rev = new \DateTime('@'.$card['lastmodified']);
			$vcard->setString('REV', $rev->format(DateTime::W3C));
		}
		return $vcard;
	}

	public static function getPropertyLineByChecksum($vcard, $checksum) {
		$line = null;
		for($i=0;$i<count($vcard->children);$i++) {
			if(md5($vcard->children[$i]->serialize()) == $checksum ) {
				$line = $i;
				break;
			}
		}
		return $line;
	}

	/**
	 * @return array of vcard prop => label
	 */
	public static function getIMOptions($im = null) {
		$l10n = self::$l10n;
		$ims = array(
				'jabber' => array(
					'displayname' => (string)$l10n->t('Jabber'),
					'xname' => 'X-JABBER',
					'protocol' => 'xmpp',
				),
				'aim' => array(
					'displayname' => (string)$l10n->t('AIM'),
					'xname' => 'X-AIM',
					'protocol' => 'aim',
				),
				'msn' => array(
					'displayname' => (string)$l10n->t('MSN'),
					'xname' => 'X-MSN',
					'protocol' => 'msn',
				),
				'twitter' => array(
					'displayname' => (string)$l10n->t('Twitter'),
					'xname' => 'X-TWITTER',
					'protocol' => null,
				),
				'googletalk' => array(
					'displayname' => (string)$l10n->t('GoogleTalk'),
					'xname' => null,
					'protocol' => 'xmpp',
				),
				'facebook' => array(
					'displayname' => (string)$l10n->t('Facebook'),
					'xname' => null,
					'protocol' => 'xmpp',
				),
				'xmpp' => array(
					'displayname' => (string)$l10n->t('XMPP'),
					'xname' => null,
					'protocol' => 'xmpp',
				),
				'icq' => array(
					'displayname' => (string)$l10n->t('ICQ'),
					'xname' => 'X-ICQ',
					'protocol' => 'icq',
				),
				'yahoo' => array(
					'displayname' => (string)$l10n->t('Yahoo'),
					'xname' => 'X-YAHOO',
					'protocol' => 'ymsgr',
				),
				'skype' => array(
					'displayname' => (string)$l10n->t('Skype'),
					'xname' => 'X-SKYPE',
					'protocol' => 'skype',
				),
				'qq' => array(
					'displayname' => (string)$l10n->t('QQ'),
					'xname' => 'X-SKYPE',
					'protocol' => 'x-apple',
				),
				'gadugadu' => array(
					'displayname' => (string)$l10n->t('GaduGadu'),
					'xname' => 'X-SKYPE',
					'protocol' => 'x-apple',
				),
		);
		if(is_null($im)) {
			return $ims;
		} else {
			$ims['ymsgr'] = $ims['yahoo'];
			$ims['gtalk'] = $ims['googletalk'];
			return isset($ims[$im]) ? $ims[$im] : null;
		}
	}

	/**
	 * @return types for property $prop
	 */
	public static function getTypesOfProperty($prop) {
		$l = self::$l10n;
		switch($prop) {
			case 'ADR':
			case 'IMPP':
				return array(
					'WORK' => $l->t('Work'),
					'HOME' => $l->t('Home'),
					'OTHER' =>  $l->t('Other'),
				);
			case 'TEL':
				return array(
					'HOME'  =>  $l->t('Home'),
					'CELL'  =>  $l->t('Mobile'),
					'WORK'  =>  $l->t('Work'),
					'TEXT'  =>  $l->t('Text'),
					'VOICE' =>  $l->t('Voice'),
					'MSG'   =>  $l->t('Message'),
					'FAX'   =>  $l->t('Fax'),
					'VIDEO' =>  $l->t('Video'),
					'PAGER' =>  $l->t('Pager'),
					'OTHER' =>  $l->t('Other'),
				);
			case 'EMAIL':
				return array(
					'WORK' => $l->t('Work'),
					'HOME' => $l->t('Home'),
					'INTERNET' => $l->t('Internet'),
					'OTHER' =>  $l->t('Other'),
				);
		}
	}

	/**
	 * @brief returns the vcategories object of the user
	 * @return (object) $vcategories
	 */
	public static function getVCategories() {
		if (is_null(self::$categories)) {
			if(\OC_VCategories::isEmpty('contact')) {
				self::scanCategories();
			}
			self::$categories = new \OC_VCategories('contact',
				null,
				self::getDefaultCategories());
		}
		return self::$categories;
	}

	/**
	 * @brief returns the categories for the user
	 * @return (Array) $categories
	 */
	public static function getCategories($format = null) {
		$categories = self::getVCategories()->categories($format);
		return ($categories ? $categories : self::getDefaultCategories());
	}

	/**
	 * @brief returns the default categories of ownCloud
	 * @return (array) $categories
	 */
	public static function getDefaultCategories() {
		return array(
			(string)self::$l10n->t('Friends'),
			(string)self::$l10n->t('Family'),
			(string)self::$l10n->t('Work'),
			(string)self::$l10n->t('Other'),
		);
	}

	/**
	 * scan vcards for categories.
	 * @param $vccontacts VCards to scan. null to check all vcards for the current user.
	 */
	public static function scanCategories($vccontacts = null) {
		if (is_null($vccontacts)) {
			$vcaddressbooks = Addressbook::all(OCP\USER::getUser());
			if(count($vcaddressbooks) > 0) {
				$vcaddressbookids = array();
				foreach($vcaddressbooks as $vcaddressbook) {
					if($vcaddressbook['userid'] === OCP\User::getUser()) {
						$vcaddressbookids[] = $vcaddressbook['id'];
					}
				}
				$start = 0;
				$batchsize = 10;
				$categories = new \OC_VCategories('contact');
				while($vccontacts =
					OCA\Contacts\VCard::all($vcaddressbookids, $start, $batchsize)) {
					$cards = array();
					foreach($vccontacts as $vccontact) {
						$cards[] = array($vccontact['id'], $vccontact['carddata']);
					}
					\OCP\Util::writeLog('contacts',
						__CLASS__.'::'.__METHOD__
							.', scanning: '.$batchsize.' starting from '.$start,
						\OCP\Util::DEBUG);
					// only reset on first batch.
					$categories->rescan($cards,
						true,
						($start == 0 ? true : false));
					$start += $batchsize;
				}
			}
		}
	}

	/**
	 * check VCard for new categories.
	 * @see OC_VCategories::loadFromVObject
	 */
	public static function loadCategoriesFromVCard($id, \OC_VObject $contact) {
		self::getVCategories()->loadFromVObject($id, $contact, true);
	}

	/**
	 * @brief Get the last modification time.
	 * @param $contact OC_VObject|integer
	 * $return DateTime | null
	 */
	public static function lastModified($contact) {
		if(is_numeric($contact)) {
			$card = VCard::find($contact);
			return ($card ? new \DateTime('@' . $card['lastmodified']) : null);
		} elseif($contact instanceof \OC_VObject) {
			$rev = $contact->getAsString('REV');
			if ($rev) {
				return \DateTime::createFromFormat(DateTime::W3C, $rev);
			}
		}
	}

	public static function cacheThumbnail($id, OC_Image $image = null) {
		if(\OC_Cache::hasKey(self::THUMBNAIL_PREFIX . $id)) {
			return \OC_Cache::get(self::THUMBNAIL_PREFIX . $id);
		}
		if(is_null($image)) {
			$vcard = self::getContactVCard($id);

			// invalid vcard
			if(is_null($vcard)) {
				\OCP\Util::writeLog('contacts',
					__METHOD__.' The VCard for ID ' . $id . ' is not RFC compatible',
					\OCP\Util::ERROR);
				return false;
			}
			$image = new \OC_Image();
			$photo = $vcard->getAsString('PHOTO');
			if(!$photo) {
				return false;
			}
			if(!$image->loadFromBase64($photo)) {
				return false;
			}
		}
		if(!$image->centerCrop()) {
			\OCP\Util::writeLog('contacts',
				'thumbnail.php. Couldn\'t crop thumbnail for ID ' . $id,
				\OCP\Util::ERROR);
			return false;
		}
		if(!$image->resize(self::THUMBNAIL_SIZE)) {
			\OCP\Util::writeLog('contacts',
				'thumbnail.php. Couldn\'t resize thumbnail for ID ' . $id,
				\OCP\Util::ERROR);
			return false;
		}
		 // Cache for around a month
		\OC_Cache::set(self::THUMBNAIL_PREFIX . $id, $image->data(), 3000000);
		\OCP\Util::writeLog('contacts', 'Caching ' . $id, OCP\Util::DEBUG);
		return \OC_Cache::get(self::THUMBNAIL_PREFIX . $id);
	}
}
