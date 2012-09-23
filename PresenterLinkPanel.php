<?php

/**
 * @author Daniel Robenek
 * @license MIT
 */

namespace Extras\Debug;

use Nette\Object;
use Nette\Diagnostics\IBarPanel;
use Nette\Utils\Html;
use Nette\Application\UI\Presenter;
use Nette\Diagnostics\Debugger;
use Nette\Templating\FileTemplate;
use Nette\Latte\Engine as LatteFilter;
use Nette\Reflection\Method as MethodReflection;
use Nette\Reflection\ClassType as ClassReflection;
use Nette\Templating\IFileTemplate;

class PresenterLinkPanel extends Object implements IBarPanel {

	/** @var Presenter */
	private $presenter;

	const ACTIVE = 1;
	const PARENTS = 2;
	const BOTH = 3;

	function __construct(Presenter $presenter) {
		$this->presenter = $presenter;
		Debugger::addPanel($this, $this->getId());
	}

	public function getPresenter() {
		return $this->presenter;
	}

	protected function getAppDir() {
		return $this->getPresenter()->getContext()->params["appDir"];
	}

	public function getId() {
		return "presenter-link-panel";
	}

	public function getTab() {
		$method = $this->getActionMethodReflection();
		if($method === null)
			$method = $this->getRenderMethodReflection();
		$presenter = self::getEditorLink($this->getPresenter()->getReflection()->getFileName(), $method === null ? $this->getPresenter()->getReflection()->getStartLine() : $method->getStartLine());
		$template = self::getEditorLink($this->getTemplateFileName());
		return Html::el("span")
			->add(
				Html::el("img")
				->src("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAL+SURBVBgZBcFNaJtlAMDx//ORjzZbs7TJkmowbJcdZqr1oNavCiIIMraBh0IY7uZx8+OiVw9SQZgXp3gR3A5OtIigcxMcylyqVPADh0WNpO2bpk2bvm3e5P163sffT1hrATj/2drDwKXjR7JzwyhhGCVEScIoTlzgAOgBBugDO8DHwA0NAJDE8SMPVA7NvTpfAgAAwAuT/DBM8n3fVMMIDgLDf70BX//jPQtc1AAASRyXJ9ICgLU9Q0oItAClIZOS3JeRKClJKZitjnFPPjf54U/OOxIAwETRRE5DnMBBKHAj2AvA9cH1YWcEWwMDwOtX28wdy3F/MVXSAAAmiiYPpyVeAJ5vkFKgAaVAKlAIlIAEEGaf5r99fmm7jgYAMGFYzo8p3FHMMLBIaVESpBEoCQqLUoBVdPcD3r359z5wXgMAxGFYK0+kcH1LDGBBGYG0gAGFRVtJYsGkDHEYH/vi5cd3JQCACYNaJZ/BCy1CghICCUhAAADCgrUQBwEmDAyABnjuzetjWsl0JiUJjUFiAYsFDAIAAUgJkTEMvGEM7ANogDgIS7lcFinAD3xav/2Iu/4npakCTneHk0+d4dDhSW5f/4jfiwUek1uy67Rfm59/6z0NYMJgXOfSWBOxfONT8tLjxXMNPM9jfX2dZvMrVCrL2dOn0FrR6XTkysrK2+12uySeuHClCFw+Mz/7wvHsFs3vv2WhscDVT77kr1/vMF2pUK/X6XQ69Ho9OpubpI9Ut155qXF0aWnJ1SYMnwGeX7nb4k77Z2aq4wD0y6cYDG+xsLBAoVBgMBiwvb3N5fc/YHf8wW+Ac/l8PqNNFD10+umZsTcaj3Ltmkez2QSgtvs5a9KyuLhILpcDwPM8bJIwtXv7STjJxsaGr00UtTZ7Lldu3iXU0/TdAT98d4v6zAz1ep1ut8vq6iqZTIZarUa5XMYPo6PLy8t7juNsitnGpSJwEahhk6KK9qpToz9O3Fsp6kw6LYSA1qhEdnyCaVpYm9go8H3Hcbqe5539H/YvZvvl5HpaAAAAAElFTkSuQmCC")
			);
	}


	public function getPanel() {
		$template = new FileTemplate(dirname(__FILE__) . '/template.latte');
		$template->registerFilter(new LatteFilter());
		$template->registerHelper("editorLink", callback(__CLASS__, "getEditorLink"));
		$template->registerHelper("substr", "substr");

		$template->presenterClass = $this->getPresenter()->getReflection();
		$template->actionName = $this->getPresenter()->getAction(true);
		$template->templateFileName = $this->getTemplateFileName();
		$template->layoutFileName = $this->getLayoutFileName();
		$template->appDirPathLength = strlen(realpath($this->getAppDir()));


		$template->interestedMethods = $this->getInterestedMethodReflections();

		$template->parentClasses = $this->getParentClasses();
		$template->componentMethods = $this->getComponentMethods();

		return $template->__toString();
	}

	protected function getInterestedMethodNames() {
		return array(
			"startup" => self::BOTH,
			$this->getActionMethodName() => self::BOTH,
			$this->getRenderMethodName() => self::BOTH,
			"beforeRender" => self::BOTH,
			"afterRender" => self::BOTH,
			"shutdown" => self::BOTH,
			"formatLayoutTemplateFiles" => self::BOTH,
			"formatTemplateFiles" => self::BOTH,
		);
	}

	private function getTemplateFileName() {
		$template = $this->getPresenter()->getTemplate();
		$templateFile = $template->getFile();
		if ($template instanceof IFileTemplate && !$template->getFile()) {
			$files = $this->getPresenter()->formatTemplateFiles();
			foreach ($files as $file) {
				if (is_file($file)) {
					$templateFile = $file;
					break;
				}
			}
			if (!$templateFile)
				$templateFile = str_replace($this->getAppDir(), "\xE2\x80\xA6", reset($files));
		}
		if($templateFile !== null)
			$templateFile = realpath($templateFile);
		return $templateFile;
	}

	private function getLayoutFileName() {
		$layoutFile = $this->getPresenter()->getLayout();
		if($layoutFile === null) {
			$files = $this->getPresenter()->formatLayoutTemplateFiles();
			foreach ($files as $file) {
				if (is_file($file)) {
					$layoutFile = $file;
					break;
				}
			}
			if (!$layoutFile)
				$layoutFile = str_replace($this->getAppDir(), "\xE2\x80\xA6", reset($files));
		}
		if($layoutFile !== null)
			$layoutFile = realpath($layoutFile);
		return $layoutFile;
	}

	private function getActionMethodName() {
		return "action" . ucfirst($this->getPresenter()->getAction(false));
	}

	private function getRenderMethodName() {
		return "render" . ucfirst($this->getPresenter()->getAction(false));
	}

	private function getInterestedMethodReflections() {
		$interestedMethods = $this->getInterestedMethodNames();
		$cr = $this->getPresenter()->getReflection();
		$methods = array();
		foreach($interestedMethods as $methodName => $scope) {
			if($scope & self::ACTIVE && $cr->hasMethod($methodName)) {
				$method = $cr->getMethod($methodName);
				if($method->getDeclaringClass()->getName() == $cr->getName())
					$methods[] = $method;
			}
		}
		return $methods;
	}

	private function getParentClasses() {
		$interestedMethods = $this->getInterestedMethodNames();
		$parents = array();
		$cr = $this->getPresenter()->getReflection()->getParentClass();
		while($cr !== null && $cr->getName() != "Presenter" && $cr->getName() != "Nette\Application\UI\Presenter") {
			$methods = array();
			foreach($interestedMethods as $methodName => $scope) {
				if($scope & self::PARENTS && $cr->hasMethod($methodName)) {
					$method = $cr->getMethod($methodName);
					if($method->getDeclaringClass()->getName() == $cr->getName())
						$methods[] = $method;
				}
			}
			$parents[] = array(
				"reflection" => $cr,
				"methods" => $methods,
			);
			$cr = $cr->getParentClass();
		}
		return $parents;
	}

	private function getComponentMethods() {
		$components = (array)$this->getPresenter()->getComponents(false);
		$methods = $this->getPresenter()->getReflection()->getMethods();
		$result = array();
		foreach($methods as $method) {
			if(strpos($method->getName(), "createComponent") === 0 && strlen($method->getName()) > 15) {
					$componentName = substr($method->getName(), 15);
					$componentName{0} = strtolower($componentName{0});
					$isUsed = isset($components[$componentName]);
					$result[] = array("method" => $method, "isUsed" => $isUsed);
			}
		}
		return $result;
	}

	private function getActionMethodReflection() {
		$method = $this->getActionMethodName();
		if($this->getPresenter()->getReflection()->hasMethod($method))
			return $this->getPresenter()->getReflection()->getMethod($method);
		else
			return null;
	}

	private function getRenderMethodReflection() {
		$method = $this->getRenderMethodName();
		if($this->getPresenter()->getReflection()->hasMethod($method))
			return $this->getPresenter()->getReflection()->getMethod($method);
		else
			return null;
	}

	public static function getEditorLink($file, $line = 1) {
		if($file instanceof MethodReflection || $file instanceof ClassReflection) {
			$line = $file->getStartLine();
			$file = $file->getFileName();
		}
		$line = (int)$line;
		return strtr(Debugger::$editor, array('%file' => $file, '%line' => $line));
	}

}
