<?php

namespace Yadup;

use \Nette\Database;

/**
 * Executive part of the updator
 */
class UpdatorService extends \Nette\Object {

	/** @var string */
	private $sqlDir;
	private $sqlExt;
	private $dbUpdateTable;
	private $dbName;
	private $definerUser;
	private $definerHost;

	/** @var \Nette\Database\Context */
	private $db;

	public function __construct(
		$sqlDir, 
		$sqlExt, 
		$dbUpdateTable, 
		$definerUser, 
		$definerHost,
		Database\Connection $dbConnection, 
		Database\IStructure $structure
	) {
		$this->sqlDir = $sqlDir . DIRECTORY_SEPARATOR;
		$this->sqlExt = $sqlExt;
		$this->dbUpdateTable = $dbUpdateTable;
		$this->db = new Database\Context($dbConnection, $structure);
		$this->dbName = $this->getDbNameFromDsn($dbConnection->getDsn());
		$this->definerUser = $definerUser;
		$this->definerHost = $definerHost;
	}

	/**
	 * @param \Nette\Database\Context $db
	 * @param string $dbUpdateTable
	 * @return boolean
	 */
	public static function isUpdateTableCreated($db, $dbUpdateTable) {
		$updTableInDb = $db->query("SHOW TABLES LIKE '{$dbUpdateTable}'")->fetch();
		return is_object($updTableInDb) ? true : false;
	}

	/**
	 * Create file with given SQL update. Name will be formatted timestamp of now.
	 * @param bool $isFull If SQL is full database dump
	 * @param string $sql
	 * @return bool
	 */
	public function createUpdate($isFull, $sql) {
		$createdAt = new \Nette\Utils\DateTime();
		$formatted = $createdAt->format("Y-m-d_H-i-s");
		$tail = $isFull ? "_full" : "";
		$filename = $this->sqlDir . $formatted . $tail . $this->sqlExt;
		$bytesOrState = file_put_contents($filename, $sql);
		return ($bytesOrState === false) ? false : true;
	}

	/**
	 * Parse one file and do its queries
	 * @param string $filename
	 * @throws \Exception
	 */
	public function runFile($filename) {
		$file = $this->sqlDir . $filename;
		if (!file_exists($file)) {
			throw new \Exception("Unable to locate file '" . $file . "'.");
		}
		if (($content = file_get_contents($file)) === false) {
			throw new \Exception("Unable to read file '" . $file . "'.");
		}
		$result = $this->parseAndRunFile($content);
		if (is_array($result)) {
			return array(
				"message" => "Error:<br/>"
					. $result["message"] . "<br/><br/>"
					. "File: <br/>'" . $file . "' <br/><br/>"
					. "Query: <br/>",
				"sql" => $result["sql"],
			);
		}
		$this->setUpdateAsDone($filename);
		return $result;
	}

	/**
	 * Mark given file as done in database updates table.
	 * @param string $filename
	 * @throws \Exception
	 */
	private function setUpdateAsDone($filename) {
		if (!$this->isUpdateTableCreated($this->db, $this->dbUpdateTable)) {
			$this->createUpdateTable();
		}
		$matches = array();
		if (!preg_match("/(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})/", $filename, $matches)) {
			throw new \Exception("Filename '{$filename}' is not in valid format.");
		}
		list(, $date, $time) = $matches;
		$this->db->queryArgs("REPLACE INTO `{$this->dbUpdateTable}` (created_at) VALUES (?)", array(
			$date . " " . strtr($time, "-", ":"),
		));
	}

	private function createUpdateTable() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `{$this->dbUpdateTable}` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`created_at` datetime NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `created_at` (`created_at`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	}

	/**
	 * @param string $dsn
	 * @return string
	 */
	private function getDbNameFromDsn($dsn) {
		$a = explode(";", $dsn);
		foreach ($a as $v) {
			if (preg_match("/dbname=(.*)/", $v, $matches)) {
				return $matches[1];
			}
		}
	}
	
	/**
	 * Import taken from Adminer, slightly modified
	 *
	 * @param string $query
	 * @return int number of done queries OR array in case of error
	 *
	 * @author Jakub Vrána, Jan Tvrdík, Michael Moravec, Martin Lukeš
	 * @license Apache License
	 */
	private function parseAndRunFile($query) {
		$delimiter = ';';
		$offset = 0;
		$state = array();
		try {
			$this->db->beginTransaction();
			while ($query != '') {
				if (!$offset && preg_match('~^\\s*DELIMITER\\s+(.+)~i', $query, $match)) {
					$delimiter = $match[1];
					$query = substr($query, strlen($match[0]));
				} else {
					preg_match('(' . preg_quote($delimiter) . '|[\'`"]|/\\*|-- |#|$)', $query, $match, PREG_OFFSET_CAPTURE, $offset); // should always match
					$found = $match[0][0];
					$offset = $match[0][1] + strlen($found);
					if (!$found && rtrim($query) === '') {
						break;
					}
					if (!$found || $found == $delimiter) { // end of a query
						$q = substr($query, 0, $match[0][1]);
						
						//change definer (must be previously definened)
						if (!empty($this->definerUser) || !empty($this->definerHost)) {
							$definerMatches = array();
							preg_match('/(DEFINER\s*=\s*)(`[^`]+`)@(`[^`]+`)/', $q, $definerMatches);
							$newUser = empty($this->definerUser) ? $definerMatches[2] : $this->definerUser;
							$newHost = empty($this->definerHost) ? $definerMatches[3] : $this->definerHost;
							$q = preg_replace('/(DEFINER\s*=\s*)(`[^`]+`)@(`[^`]+`)/', '$1`' . $newUser . '`@`' . $newHost . '`', $q);
						}
						
						$state[] = $this->db->query($q);
						$query = substr($query, $offset);
						$offset = 0;
					} else { // find matching quote or comment end
						while (preg_match('~' . ($found == '/*' ? '\\*/' : (preg_match('~-- |#~', $found) ? "\n" : "$found|\\\\.")) . '|$~s', $query, $match, PREG_OFFSET_CAPTURE, $offset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
							$s = $match[0][0];
							$offset = $match[0][1] + strlen($s);
							if ($s[0] !== '\\') {
								break;
							}
						}
					}
				}
			}
			$this->db->commit();
			$result = count($state);
		} catch (\Exception $ex) {
			$this->db->rollBack();
			$result = array(
				"message" => $ex->getMessage(),
				"sql" => $q,
			);
		}
		$this->db->query('USE `' . $this->dbName . '`'); // revert used database
		return $result;
	}

}
