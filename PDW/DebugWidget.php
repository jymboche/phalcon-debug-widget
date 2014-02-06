<?php

namespace PDW;

use Phalcon\Db\Profiler as Profiler,
	Phalcon\Escaper as Escaper,
	Phalcon\Mvc\Url as URL,
	Phalcon\Mvc\View as View;

class DebugWidget implements \Phalcon\DI\InjectionAwareInterface
{

	protected $_di;
	private $startTime;
	private $endTime;
	private $queryCount = 0;
	protected $_profiler;
	protected $_viewsRendered = array();
        protected $_serviceNames = array();

	public function __construct(
		$di,
		$serviceNames =
			array(
				'db' => array('db'),
				'dispatch' => array('dispatcher'),
				'view' => array('view')
			)
	) {
		$this->_di = $di;
		$this->startTime = microtime(true);
		$this->_profiler = new Profiler();

		$eventsManager = $di->get('eventsManager');

		foreach ($di->getServices() as $service) {
			$name = $service->getName();
			foreach ($serviceNames as $eventName => $services) {
				if (in_array($name, $services)) {
					$service->setShared(true);
					$di->get($name)->setEventsManager($eventsManager);
                                        break;
				}
			}
		}
		foreach (array_keys($serviceNames) as $eventName) {
			$eventsManager->attach($eventName, $this);
		}
		$this->_serviceNames = $serviceNames;
	}

	public function setDI($di)
	{
		$this->_di = $di;
	}

	public function getDI()
	{
		return $this->_di;
	}

	public function getServices($event)
	{
		return $this->_serviceNames[$event];
	}

	public function beforeQuery($event, $connection)
	{
		$this->_profiler->startProfile($connection->getRealSQLStatement());
	}

	public function afterQuery($event, $connection)
	{
		$this->_profiler->stopProfile();
		$this->queryCount++;
	}

	/**
	 * Gets/Saves information about views and stores truncated viewParams.
	 *
	 * @param unknown $event
	 * @param unknown $view
	 * @param unknown $file
	 */
	public function beforeRenderView($event,$view,$file)
	{
		$params = array();
		$toView = $view->getParamsToView();
		$toView = !$toView? array() : $toView;
		foreach ($toView as $k=>$v) {
			if (is_object($v)) {
				$params[$k] = get_class($v);
			} elseif(is_array($v)) {
				$array = array();
				foreach ($v as $key=>$value) {
					if (is_object($value)) {
						$array[$key] = get_class($value);
					} elseif (is_array($value)) {
						$array[$key] = 'Array[...]';
					} else {
						$array[$key] = $value;
					}
				}
				$params[$k] = $array;
			} else {
				$params[$k] = (string)$v;
			}
		}

		$this->_viewsRendered[] = array(
			'path'=>$view->getActiveRenderPath(),
			'params'=>$params,
			'controller'=>$view->getControllerName(),
			'action'=>$view->getActionName(),
		);
	}


	public function afterRender($event,$view,$viewFile)
	{
		$this->endTime = microtime(true);
		$content = $view->getContent();
		$scripts = $this->getInsertScripts();
		$scripts .= "</head>";
		$content = str_replace("</head>", $scripts, $content);
		$rendered = $this->renderToolbar();
		$rendered .= "</body>";
		$content = str_replace("</body>", $rendered, $content);

		$view->setContent($content);
	}

	/**
	 * Returns scripts to be inserted before <head>
	 * Since setBaseUri may or may not end in a /, double slashes are removed.
	 *
	 * @return string
	 */
	public function getInsertScripts()
	{
		$escaper = new Escaper();
		$url = $this->getDI()->get('url');
		$scripts = "";

		$css = array('/pdw-assets/style.css', '/pdw-assets/lib/prism/prism.css');
		foreach ($css as $src) {
			$link = $url->get($src);
			$link = str_replace("//", "/", $link);
			$scripts .= "<link rel='stylesheet' type='text/css' href='" . $escaper->escapeHtmlAttr($link) . "' />";
		}

		$js = array(
				'/pdw-assets/jquery.min.js',
				'/pdw-assets/lib/prism/prism.js',
				'/pdw-assets/pdw.js'
		);
		foreach ($js as $src) {
			$link = $url->get($src);
			$link = str_replace("//", "/", $link);
			$scripts .= "<script tyle='text/javascript' src='" . $escaper->escapeHtmlAttr($link) . "'></script>";
		}

		return $scripts;
	}

	public function renderToolbar()
	{
		$view = new View();
		$viewDir = dirname(__FILE__) .'/views/';
		$view->setViewsDir($viewDir);

		// set vars
		$view->debugWidget = $this;

		$content = $view->getRender('toolbar', 'index');
		return $content;
	}

	public function getStartTime()
	{
		return $this->startTime;
	}

	public function getEndTime()
	{
		return $this->endTime;
	}

	public function getRenderedViews()
	{
		return $this->_viewsRendered;
	}

	public function getQueryCount()
	{
		return $this->queryCount;
	}

	public function getProfiler()
	{
		return $this->_profiler;
	}
}
