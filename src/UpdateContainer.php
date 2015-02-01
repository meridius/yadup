<?php

namespace Yadup;

use \Nette\Utils\Finder;

/**
 * Used to find and store updates
 */
class UpdateContainer extends \Nette\Object {

	/** @var string */
	private $sqlDir;
	private $sqlExt;
	private $dbUpdateTable;

	/** @var UpdateEntity[] */
	private $updates = array();

	/** @var \Nette\Database\Context */
	private $db;

	public function __construct($sqlDir, $sqlExt, $dbUpdateTable, \Nette\Database\Context $db) {
		$this->sqlDir = $sqlDir;
		$this->sqlExt = $sqlExt;
		$this->dbUpdateTable = $dbUpdateTable;
		$this->db = $db;
	}
	
	/**
	 * Throw away found updates
	 * @return UpdateContainer
	 */
	public function clearStack() {
		$this->updates = array();
		return $this;
	}

	/**
	 * Find updates in database
	 * @return UpdateContainer
	 */
	public function findUpdatesFromDb() {
		if (\Yadup\UpdatorService::isUpdateTableCreated($this->db, $this->dbUpdateTable)) {
			foreach ($this->db->table($this->dbUpdateTable)->fetchAll() as $upd) {
				$update = $this->getOrCreateUpdate($upd["created_at"]);
				$update->inDb = true;
			}
		}
		return $this;
	}

	/**
	 * Find updates in files
	 * @return UpdateContainer
	 */
	public function findUpdatesFromFiles() {
		$pDate = "\d{4}-\d{2}-\d{2}";
		$pTime = "\d{2}-\d{2}-\d{2}";
		$pattern = "/^({$pDate})_({$pTime})(_full)?$/";
		/* @var $file \SplFileInfo */
		foreach (Finder::findFiles('*' . $this->sqlExt)->in($this->sqlDir) as $absPath => $file) {
			$name = $file->getBasename($this->sqlExt);
			$matches = array();
			if (!preg_match($pattern, $name, $matches)) {
				continue;
			}
			$date = $matches[1];
			$time = preg_replace("/-/", ":", $matches[2]);

			$update = $this->getOrCreateUpdate($date . " " . $time);
			$update->inFile = true;
			$update->filename = $file->getBasename();
			$update->isFull = array_key_exists(3, $matches);
			$update->body = htmlspecialchars(file_get_contents($absPath));
		}
		return $this;
	}

	/**
	 * Get found updates
	 * @param boolean $all <br/>
	 *  (true) - all found updates <br/>
	 *  (false) - updates beginning last full update; default
	 * @return UpdateEntity[]
	 */
	public function getUpdates($all = false) {
		if ($all) {
			ksort($this->updates);
			return $this->updates;
		} else {
			krsort($this->updates);
			$updates = array();
			foreach ($this->updates as $dateTime => $update) {
				$updates[$dateTime] = $update;
				if ($update->isFull) {
					break;
				}
			}
			ksort($updates);
			return $updates;
		}
	}

	/**
	 * Count found updates in database
	 * @param bool $all
	 * @return UpdatesCountEntity
	 */
	public function getUpdatesCount($all = false) {
		$updates = $this->getUpdates($all);
		$count = new UpdatesCountEntity();
		foreach ($updates as $update) {
			if ($update->inDb && !$update->inFile) {
				$count->ahead++;
			} else if (!$update->inDb && $update->inFile) {
				$count->behind++;
			}
		}
		return $count;
	}

	/**
	 * Return requested update if any or prepare record to create one.
	 * @param string $dateTime
	 * @return UpdateEntity
	 */
	private function getOrCreateUpdate($dateTime) {
		$dt = new \Nette\Utils\DateTime();
		$dateTimeObj = $dt->from($dateTime);
		$timestamp = $dateTimeObj->getTimestamp();
		if (!array_key_exists($timestamp, $this->updates)) {
			$this->updates[$timestamp] = new UpdateEntity($dateTime);
		}
		return $this->updates[$timestamp];
	}

}


/**
 * Entity of one found update
 */
class UpdateEntity extends \Nette\Object {

	public $inDb;
	public $inFile;
	public $isFull;
	public $filename;
	public $body;
	public $dateTime;

	public function __construct($dateTime) {
		$this->dateTime = $dateTime;
	}

}


class UpdatesCountEntity extends \Nette\Object {
	
	/** @var int */
	public $ahead = 0;
	/** @var int */
	public $behind = 0;
	
}
