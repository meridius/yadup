<?php

namespace Yadup;

use \Nette\Database;

/**
 * Render info about all found updates
 */
class UpdatorRenderService extends \Nette\Object {
	
	/** @var UpdateContainer */
	private $updateContainer;
	
	public function __construct(
		$sqlDir, 
		$sqlExt, 
		$dbUpdateTable, 
		Database\Connection $dbConnection, 
		Database\IStructure $structure
	) {
		if (!file_exists($sqlDir)) {
			throw new \Exception("Specified path to a directory with SQL updates '{$sqlDir}' could not be found. Create it or specify a different one in config file.");
		}
		$dbContext = new Database\Context($dbConnection, $structure);
		$this->updateContainer = new UpdateContainer($sqlDir, $sqlExt, $dbUpdateTable, $dbContext);
		$this->findUpdates();
	}
	
	/**
	 * Wrapper to find all updates in DB and files.
	 * @param bool $performNewSearch defaults to false
	 */
	public function findUpdates($performNewSearch = false) {
		if ($performNewSearch) {
			$this->updateContainer->clearStack();
		}
		$this->updateContainer
			->findUpdatesFromFiles()
			->findUpdatesFromDb();
	}
	
	/**
	 * Return textual representation of updates' count.
	 * @param bool $showAll defaults to false meaning from last full update
	 * @return string
	 */
	public function renderUpdatesCount($showAll = false) {
		$count = $this->updateContainer->getUpdatesCount($showAll);
		return ($count->behind + $count->ahead === 0) ? "DB is current" :
			'<span title="behind (on disk, not in DB)">' . $count->behind . '-</span>' .
			' / ' .
			'<span title="ahead (in DB, not on disk)">' . $count->ahead . '+</span>';
	}

	/**
	 * Return overview table of found updates.
	 * @param bool $showAll defaults to false meaning from last full update
	 * @return string
	 */
	public function renderTable($showAll = false) {
		$s = '<table class="yadup-controls-list">
						<thead>
							<tr>
								<th colspan="5"></th>
							</tr>
						</thead>
						<tbody>';
		$updates = $this->updateContainer->getUpdates($showAll);
		/* @var $update UpdateEntity */
		foreach ($updates as $timestamp => $update) {
			$inDb = $update->inDb ? "yes" : "no";
			$inFile = $update->inFile ? "yes" : "no";
			$isFull = $update->isFull ? "yes" : "no";
			$s .= '<tr>
							<td>' . $update->dateTime . '</td>
							<td>' . $inDb . '</td>
							<td>' . $inFile . '</td>
							<td>' . $isFull . '</td>
							<td>';
			if ($update->inFile) {
				$s .= '<input name="' . $timestamp . '" type="checkbox" checked'
					. ' data-filename="' . $update->filename . '"'
					. ' data-sql="' . $update->body . '" />';
			}
			$s .= '</td></tr>';
		}
		return $s . '</tbody></table>';
	}
	
}
