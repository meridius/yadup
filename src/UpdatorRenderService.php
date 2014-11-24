<?php

namespace Yadup;

/**
 * Render info about all found updates
 */
class UpdatorRenderService extends \Nette\Object {
	
	/** @var UpdateContainer */
	private $updateContainer;
	
	public function __construct($sqlDir, $sqlExt, $dbUpdateTable, $dbConnection) {
		if (!($dbConnection instanceof \Nette\Database\Connection)) {
			throw new \Exception("Yadup config property 'dbConnection' is not of type \Nette\Database\Connection.");
		}
		if (!file_exists($sqlDir)) {
			throw new \Exception("Please specify path to a directory with SQL updates.");
			
		}
		$dbContext = new \Nette\Database\Context($dbConnection);
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
	 * Return updates' count wrapped in span tags.
	 * @param bool $showAll defaults to false meaning from last full update
	 * @return string
	 */
	public function renderUpdatesCount($showAll = false) {
		return 
			'<span title="Updates in DB">' . 
			$this->updateContainer->getCountDbUpdates($showAll) . '-</span>' .
			' / ' .
			'<span title="Updates on disk">' . 
			$this->updateContainer->getCountFileUpdates($showAll) . '+</span>';
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
