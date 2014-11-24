<?php

namespace Yadup;

/**
 * YADUP - Yet Another Database Updator Panel
 * @author Martin Lukeš
 */
class Panel extends \Nette\Object implements \Tracy\IBarPanel {

	/** @var string */
	private $jushScriptFileName;
	private $yadupScriptFileName;
	private $jushStyleFileName;
	private $yadupStyleFileName;

	/** @var \Nette\Application\Application */
	private $app;

	/** @var UpdatorRenderService */
	private $updatorRenderService;

	/**
	 * 
	 * @param array $config directory with sql script files
	 */
	public function __construct($config, \Nette\Application\Application $app, UpdatorRenderService $updatorRenderService) {
		$this->jushScriptFileName = $config["jushScriptFileName"];
		$this->jushStyleFileName = $config["jushStyleFileName"];
		$this->yadupScriptFileName = $config["yadupScriptFileName"];
		$this->yadupStyleFileName = $config["yadupStyleFileName"];
		$this->updatorRenderService = $updatorRenderService;
		$this->app = $app;
	}

	/**
	 * Whole panel after hover or click on tab
	 * @return string
	 */
	public function getPanel() {
		$presenter = $this->app->getPresenter();
		$linkRun = (!$presenter) ? "" : $presenter->link("Yadup:Update:Run");
		$linkCreate = (!$presenter) ? "" : $presenter->link("Yadup:Update:Create");
		$linkShowSettings = (!$presenter) ? "" : $presenter->link("Yadup:Update:ShowSettings");
		return $this->renderStyles() . $this->renderJavascript() . '
			<div class="js-yadupPanel">
				<div class="yadup-heading">
					<h1>DB updates</h1> 
					<input name="show_all" type="checkbox" title="Show all updates / Only from last full" data-link="' . $linkShowSettings . '" />
				</div>
				<div class="yadup-controls">
					<div class="yadup-controls-header">
						<table>
							<thead>
								<tr>
									<th>timestamp</th>
									<th>in DB</th>
									<th>on disk</th>
									<th>is full</th>
									<th title="Toggle selected">run</th>
								</tr>
							</thead>
						</table>
					</div>
					<div class="yadup-controls-inner">' 
					. $this->updatorRenderService->renderTable() . '
					</div>
					<div class="yadup-controls-buttons">
						<input type="button" name="createUpdate" value="Create new" />
						<input type="button" name="runUpdates" value="Run selected" data-link="' . $linkRun . '" />
						<input type="button" name="cancel" value="Cancel" />
						<input type="button" name="saveFile" value="Save file" data-link="' . $linkCreate . '" />
					</div>
				</div>
				<div class="yadup-sqlPanel">
					<div class="yadup-sqlPanel-heading">
						<p class="yadup-sqlPanel-heading-run">
							<span></span>
							<span title="in DB"></span>
							<span title="on disk"></span>
							<span title="is full"></span>
						</p>
						<p class="yadup-sqlPanel-heading-create">
							<span title="If SQL is full database dump">
								<input id="yadup-sqlPanel-heading-create_is-full" name="is_full" type="checkbox" value="" />
								<label for="yadup-sqlPanel-heading-create_is-full">is full</label>
							</span>
						</p>
					</div>
					<div class="yadup-sqlPanel-query">
						<div class="yadup-sqlPanel-query-inner">
							<code class="jush-sql"></code>
							<textarea class="jush-sql" name="newSql" placeholder="Paste here your query"></textarea>
						</div>
					</div>
				</div>
			</div>';
	}

	/**
	 * Only tab in Tracy panel
	 * @author Icon by FatCow Web Hosting http://findicons.com/icon/164498/database_refresh#
	 * @return string
	 */
	public function getTab() {
		return '<span title="YADUP - Yet Another Database Updator Panel">'
			. '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAA'
			. 'Af8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAwZJREFUeNqM'
			. 'k2lsTFEUx39vnUVnQ1Vb7dQSpBUJWkRU0pSIWJoogojiA63lkw9iT0So2ENSTbQ08Ymgit'
			. 'C0qJ2oLQRpMahpSrX2zgxmnvtmLBGROC/n3bxz7v9/zv3f86SKw8eJRCJDDMPYFDEiuRj8'
			. 'bRLIknxGlpVlYr1pBqQfKVWASzVNm5/mTcPjcmO12RBkv7GSRDAY5P37j7kvm1rqw18pFU'
			. 'SFPylMgvnp/TMwKweCIToCoV/A6GOukoIrrhtVn5Zw2Ve9QFMpVKUfnZnVeqYk4XY7kGUT'
			. 'EkEWb03WsWlOOumdcVrjSY53cb6hmtJZeyGMoQiwRRYcZQePGkkJifTplYbT4cBuswswPH'
			. '/zmB3VU7jz7K7oIFqMnIHQOwH6dtvA2kMrsOoiVX6o0ugen4Cmalh0qyBw8i70nK112YwY'
			. 'DD0TiRKYsnwLx1wWBRKtc9l1bB+q2bQiIqqiCFfRNTs1d4vJGgSfv8CJi7GzNrfAqOHQxw'
			. 'sprpEUl++jZjmZ8tv2tpMPHjXQ0trKp88dgkTjfutJml/DB/9QZqafYFLKEdYM81N3FTxx'
			. 'ndlYdikKnrSNm7Km6aHZM6bRy5tKMBDg3sPrtLyDdMtq5maco7huAhd820n1diUvczY7K9'
			. 'qpXUHmhK2IA2JI5QcrjeTEpKiILqcTh92CLsdmIGutRNXiEvL3FFFZZFB3uwr/az+nmxfW'
			. 'ZCQw5oUopJrnC4fDPPU1CREtURE9cclMLVM4sGgVpxqLyB0A+SUSPeLNiYTh/RjTSYP7Pm'
			. 'qjIpoyq0pMSE3VmVKusHJaHlferAeHEM4N/dIRYsduoKkZLt8Cfz3r1bdtbUcURZnscXtQ'
			. 'BFgSG44XGkzdKzFvfBa6+waPfdD4DF61gqaIet9obDjFUt8lrpnz4Z1eMC87O2f0EpvdPt'
			. 'S8M0WyYFW6UvIok7ycLlSda2NO6tUrZ2sP767Yv6VBYALCn+RtJySZf2NB/kS7CPQg2vCf'
			. 'Nm4z9R0fOXt+HYXi0z92Cx266ELm/22I8N7/Sn4XYAAtWv2diTZoPQAAAABJRU5ErkJggg==" />'
			. '<span class="yadup-updates-count" title="Updates to be done (DB/files)">'
			. $this->updatorRenderService->renderUpdatesCount()
			. '</span>'
			. '</span>';
	}

	/**
	 * @return string
	 */
	private function renderJavascript() {
		return '<script type="text/javascript">'
			. file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $this->jushScriptFileName)
			. file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $this->yadupScriptFileName)
			. '</script>';
	}

	/**
	 * @return string
	 */
	private function renderStyles() {
		return '<style>'
			. file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $this->jushStyleFileName)
			. file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $this->yadupStyleFileName)
			. '</style>';
	}

}
