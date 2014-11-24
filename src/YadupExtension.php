<?php

namespace Yadup;

class YadupExtension extends \Nette\DI\CompilerExtension {

	public $defaults = array(
		"dbUpdateTable" => "_db_update",
		"dbConnection" => "@nette.database.default",
		"definerUser" => "", // to change definer it must be previously definened in query
		"definerHost" => "",
		"sqlDir" => "%appDir%/sql", // directory with sql script files
		"sqlExt" => ".sql", // extension of sql files; with "dot"
		"jushScriptFileName" => "jush.js",
		"yadupScriptFileName" => "yadup.js",
		"jushStyleFileName" => "jush_important.css", // there is no other way to disable #tracy-debug * {color:inherit;}
		"yadupStyleFileName" => "yadup.css",
	);

	public function loadConfiguration() {
		parent::loadConfiguration();
		$config = $this->getConfig($this->defaults);
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('Panel'))
			->setClass('Yadup\Panel', array($config))
			->addSetup('Tracy\Debugger::getBar()->addPanel(?)', array('@self'))
			->addTag("run");
		
		$builder->addDefinition($this->prefix("UpdatorService"))
			->setClass("Yadup\UpdatorService", array(
				$config["sqlDir"],
				$config["sqlExt"],
				$config["dbUpdateTable"],
				$config["dbConnection"],
				$config["definerUser"],
				$config["definerHost"],
			));
		
		$builder->addDefinition($this->prefix("UpdatorRenderService"))
			->setClass("Yadup\UpdatorRenderService", array(
				$config["sqlDir"],
				$config["sqlExt"],
				$config["dbUpdateTable"],
				$config["dbConnection"],
			));
	}

	public function beforeCompile() {
		parent::beforeCompile();
		$builder = $this->getContainerBuilder();
		$builder->getDefinition('nette.presenterFactory')
			->addSetup('setMapping', array(
				array('Yadup' => 'Yadup\\*Module\\*Presenter')
		));
	}

	public function afterCompile(\Nette\PhpGenerator\ClassType $class) {
		parent::afterCompile($class);
	}

}
