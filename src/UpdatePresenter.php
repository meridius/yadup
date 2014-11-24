<?php

namespace Yadup;

/**
 * Landing class for AJAX requests
 */
class UpdatePresenter extends \Nette\Application\UI\Presenter {

	/** @var \Yadup\UpdatorService @inject */
	public $updator;

	/** @var \Yadup\UpdatorRenderService @inject */
	public $updatorRender;

	/**
	 * Run queries in given file based on its name.
	 */
	public function actionRun() {
		$this->checkIsAjax();
		$filename = $this->getParameter("filename");
		$this->payload->state = true;
		$this->payload->message = "";
		$this->payload->sql = "";
		try {
			$result = $this->updator->runFile($filename);
			if (is_array($result)) {
				$this->payload->state = false;
				$this->payload->message = $result["message"];
				$this->payload->sql = $result["sql"];
			} else {
				$this->payload->queriesDone = $result;
			}
		} catch (\Exception $e) {
			$this->payload->state = false;
			$this->payload->message = $e->getMessage();
		}
		$this->sendPayload();
	}
	
	/**
	 * Create file with given SQL update.
	 */
	public function actionCreate() {
		$this->checkIsAjax();
		$sql = $this->getParameter("sql");
		$showAll = \Nette\Utils\Json::decode($this->getParameter("showAll"));
		$isFull = \Nette\Utils\Json::decode($this->getParameter("isFull"));
		if ($this->updator->createUpdate($isFull, $sql)) {
			$this->updatorRender->findUpdates(true);
			$this->payload->state = true;
			$this->payload->message = "File successfully created";
			$this->payload->table = $this->updatorRender->renderTable($showAll);
			$this->payload->updatesCount = $this->updatorRender->renderUpdatesCount($showAll);
		} else {
			$this->payload->state = false;
			$this->payload->message = "Failed!";
		}
		$this->sendPayload();
	}
	
	/**
	 * Toggle how much found updates are shown.
	 */
	public function actionShowSettings() {
		$this->checkIsAjax();
		$showAll = \Nette\Utils\Json::decode($this->getParameter("showAll"));
		$this->payload->table = $this->updatorRender->renderTable($showAll);
		$this->payload->updatesCount = $this->updatorRender->renderUpdatesCount($showAll);
		$this->sendPayload();
	}
	
	/**
	 * Basic security method
	 */
	private function checkIsAjax() {
		if (!$this->isAjax()) {
			$this->sendResponse(
				new \Nette\Application\Responses\TextResponse("You have no bussiness here.")
			);
		}
	}

}
