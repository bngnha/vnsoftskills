<?php
/**
 * @package Joomlashack Wright Framework
 * @copyright Joomlashack 2010-2013. All Rights Reserved.
 *
 * @description Wright is a framework layer for Joomla to improve stability of Joomlashack Templates
 *
 * It would be inadvisable to alter the contents of anything inside of this folder
 *
 */
defined('_JEXEC') or die('You are not allowed to directly access this file');

//Adding a check for PHP4 to cut down on support
if (version_compare(PHP_VERSION, '5', '<'))
{
	print
		'You are using an out of date version of PHP, version ' . PHP_VERSION
			. ' and our products require PHP 5.2 or greater. Please contact your host to use PHP 5.2 or greater. All versions of PHP prior to this are unsupported, by us and by the PHP community.';
	die();
}

// includes WrightTemplateBase class for customizations to the template
require_once(dirname(__FILE__) . '/template/wrighttemplatebase.php');

//build LESS via PHP
require_once(dirname(__FILE__) . '/build/build.php');

class Wright
{
	public $template;
	public $document;
	public $adapter;
	public $params;
	public $baseurl;
	public $author;

	public $revision = "1.0.0";

	private $loadBootstrap = false;

	public $_jsScripts = Array();
	public $_jsDeclarations = Array();
	public $_cssStyles = Array();

	// Urls
	private $_urlTemplate = null;
	private $_urlWright = null;
	private $_urlJS = null;

	function Wright()
	{
		// Initialize properties
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$this->document = $document;
		$this->params = $document->params;
		$this->baseurl = $document->baseurl;

		// Urls
		$this->_urlTemplate = JURI::root(true) . '/templates/' . $this->document->template;
		$this->_urlWright = $this->_urlTemplate . '/wright';
		$this->_urlFontAwesome = $this->_urlWright . '/fontawesome';
		$this->_urlJS = $this->_urlWright . '/js';

		// versions under 3.0 must load bootstrap
		if (version_compare(JVERSION, '3.0', 'lt'))
		{
			$this->loadBootstrap = true;
		}
		else
		{
			// Add JavaScript Frameworks
			JHtml::_('bootstrap.framework');
		}

		$this->author = simplexml_load_file(
			JPATH_BASE . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $this->document->template . DIRECTORY_SEPARATOR
				. 'templateDetails.xml')->author;

		if (is_file(JPATH_THEMES . DIRECTORY_SEPARATOR . $document->template . DIRECTORY_SEPARATOR . 'functions.php'))
			include_once(JPATH_THEMES . DIRECTORY_SEPARATOR . $document->template . DIRECTORY_SEPARATOR . 'functions.php');

		// Get our template for further parsing, if custom file is found
		// it will use it instead of the default file
		$path = JPATH_THEMES . '/' . $document->template . '/' . 'template.php';
		$menu = $app->getMenu();

		// If homepage, load up home.php if found, or load custom.php if found
		$lang = JFactory::getLanguage();
		if ($menu->getActive() == $menu->getDefault($lang->getTag()) && is_file(JPATH_THEMES . '/' . $document->template . '/home.php'))
			$path = JPATH_THEMES . '/' . $document->template . '/home.php';
		elseif (is_file(JPATH_THEMES . '/' . $document->template . '/custom.php'))
			$path = JPATH_THEMES . '/' . $document->template . '/custom.php';

        //
        if ($this->loadBootstrap)
            // load bootstrap JS
            $this->addJSScript($this->_urlJS . '/bootstrap.min.js');

		$build = new BuildBootstrap();
		$build->start();

		// Include our file and capture buffer
		ob_start();
		include($path);
		$this->template = ob_get_contents();
		ob_end_clean();
	}

	static function getInstance()
	{
		static $instance = null;
		if ($instance === null)
		{
			$instance = new Wright();
		}

		return $instance;
	}

	public function display()
	{


        // Setup the header
		$this->header();

		// Parse by platform
		$this->platform();

		// Parse by doctype
		$this->doctype();

		print trim($this->template);

		return true;
	}

	public function header()
	{
		// Remove mootools if set
		if ($this->document->params->get('mootools', '1') == '0')
		{
			$dochead = $this->document->getHeadData();
			reset($dochead['scripts']);
			foreach ($dochead['scripts'] as $script => $type)
			{
				if (strpos($script, 'media/system/js/caption.js') || strpos($script, 'media/system/js/mootools.js')
					|| strpos($script, 'media/system/js/mootools-core.js') || strpos($script, 'media/system/js/mootools-more.js'))
				{
					unset($dochead['scripts'][$script]);
				}
			}
			$this->document->setHeadData($dochead);
		}
		else
		{
			JHtml::_('behavior.framework', true);
		}

		// load jQuery ?
		if ($this->loadBootstrap && $loadJquery = $this->document->params->get('jquery', 0))
		{
			switch ($loadJquery)
			{
				// load jQuery locally
				case 1:
					$jquery = $this->_urlJS . '/jquery-1.8.3.min.js';
					break;
				// load jQuery from Google
				default:
					$jquery = 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js';
					break;
			}

			$this->document->addScript($jquery);
			// ensure that jQuery loads in noConflict mode to avoid mootools conflicts
			$this->document->addScriptDeclaration('jQuery.noConflict();');
		}

		$this->addJSScript($this->_urlJS . '/utils.js');
		if ($this->document->params->get('stickyFooter', 1))
		{
			$this->addJSScript($this->_urlJS . '/stickyfooter.js');
		}

		// Add header script if set
		if (trim($this->document->params->get('headerscript', '')) !== '')
		{
			$this->addJSScriptDeclaration($this->document->params->get('headerscript'));
		}

		// set custom template theme for user
		$user = JFactory::getUser();
		if (!is_null(JRequest::getVar('templateTheme', NULL)))
		{
			$user->setParam('theme', JRequest::getVar('templateTheme'));
			$user->save(true);
		}
		if ($user->getParam('theme'))
		{
			$this->document->params->set('style', $user->getParam('theme'));
		}

		// Build css
		$this->css();
	}

	private function css()
	{
		$this->_cssStyles = $this->loadCSSList();

		//$this->addCSSToHead($styles);
	}

	private function addCSSToHead($styles)
	{
		foreach ($styles as $folder => $files)
		{
			if (count($files))
			{
				foreach ($files as $style)
				{
					if ($folder == 'fontawesome')
						$sheet = $this->_urlFontAwesome . '/css/' . $style;
					else
						$sheet = JURI::root() . 'templates/' . $this->document->template . '/css/' . $style;

					$this->document->addStyleSheet($sheet);
				}
			}
		}
	}

	public function generateCSS()
	{
		$css = "\n";
		$styles = $this->_cssStyles;

		foreach ($styles as $folder => $files)
		{
			if (count($files))
			{
				foreach ($files as $style)
				{
					if ($folder == 'fontawesome')
						$sheet = $this->_urlFontAwesome . '/css/' . $style;
					else
						$sheet = JURI::root() . 'templates/' . $this->document->template . '/css/' . $style;

					$css .= '<link rel="stylesheet" href="' . $sheet . '" type="text/css" />' . "\n";
				}
			}
		}

		return $css;
	}

	private function loadCSSList()
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		jimport('joomla.environment.browser');

		$browser = JBrowser::getInstance();

		$version = explode('.', JVERSION);
		$version = $version[0] . $version[1];

		if (is_file(
			JPATH_THEMES . '/' . $this->document->template . '/css/' . 'joomla' . $version . '-' . $this->document->params->get('style') . '.css'))
			$styles['template'][] = 'joomla' . $version . '-' . $this->document->params->get('style') . '.css';

		if ($this->document->params->get('responsive', 1)
			&& is_file(
				JPATH_THEMES . '/' . $this->document->template . '/css/' . 'joomla' . $version . '-' . $this->document->params->get('style')
					. '-responsive.css'))
		{
			$styles['template'][] = 'joomla' . $version . '-' . $this->document->params->get('style') . '-responsive.css';
		}

		// Add some stuff for lovely IE if needed
		if ($browser->getBrowser() == 'msie')
		{
			// Switch to allow specific versions of IE to have additional sheets
			$major = $browser->getMajor();

			if ((int) $major <= 9)
			{
				$this->document->addScript(JURI::root() . 'templates/' . $this->document->template . '/wright/js/html5shiv.js');
			}

			if (is_file(JPATH_THEMES . '/' . $this->document->template . '/css/ie.css'))
			{
				$styles['ie'][] = 'ie.css';
			}

			switch ($major)
			{
				case '7':
					$styles['fontawesome'][] = 'font-awesome-ie7.min.css';
				// does not break for leaving defaults
				default:
					if (is_file(JPATH_THEMES . '/' . $this->document->template . '/css/ie' . $major . '.css'))
						$styles['ie'][] = 'ie' . $major . '.css';
			}
		}

		if ($this->document->direction == 'rtl' && is_file(JPATH_THEMES . '/' . $this->document->template . '/css/rtl.css'))
			$styles['template'][] = 'rtl.css';

		//Check to see if custom.css file is present, and if so add it after all other css files
		if (is_file(JPATH_THEMES . '/' . $this->document->template . '/css/custom.css'))
			$styles['template'][] = 'custom.css';

		// Include FontAwesome
		$styles['fontawesome'] = Array('font-awesome.min.css');

		return $styles;
	}

	private function doctype()
	{
		require(dirname(__FILE__) . '/doctypes/' . $this->document->params->get('doctype', 'html5') . '.php');
		$adapter_name = 'HtmlAdapter' . $this->document->params->get('doctype', 'html5');
		$adapter = new $adapter_name($this->document->params);

		foreach ($adapter->getTags() as $name => $regex)
		{
			$action = 'get' . ucfirst($name);
			$this->template = preg_replace_callback($regex, array($adapter, $action), $this->template);
		}

		// reorder columns based on the order
		$this->reorderContent();

		if (trim($this->document->params->get('footerscript')) != '')
		{
			$this->template = str_replace('</body>',
				'<script type="text/javascript">' . $this->document->params->get('footerscript') . '</script></body>', $this->template);
		}
		$this->template = str_replace('__cols__', $adapter->cols, $this->template);
	}

	/**
	 * Searches for platform specific tags, and has a callback function to
	 * handle the processing of each match
	 *
	 * @access private
	 */
	private function platform()
	{
		// Get Joomla's version to get proper platform
		jimport('joomla.version');
		$version = new JVersion();
		$file = ucfirst(str_replace('.', '', $version->RELEASE));

		// Load up the proper adapter
		require_once(dirname(__FILE__) . '/adapters/joomla.php');
		$this->adapter = new WrightAdapterJoomla($file);
		$this->template = preg_replace_callback("/<w:(.*)\/>/i", array(get_class($this), 'platformTags'), $this->template);
		return true;
	}

	private function platformTags($matches)
	{
		// Grab first match since there should only be one
		$match = $matches[1];

		// @TODO Craft a regex to better handle syntax possibilities
		if (strpos($match, '='))
		{
			$tag = substr($match, 0, strpos($match, ' '));
			$list = explode('" ', substr($match, trim(strpos($match, ' ') + 1)));

			$attributes = array();

			foreach ($list as $item)
			{
				if (strlen($item) > strrchr($item, '"'))
				{
					$name = substr($item, 0, strpos($item, '='));
					$value = substr($item, strpos($item, '"') + 1);
					$attributes[$name] = $value;
				}
			}
			$config = array(trim($tag) => $attributes);
		}
		else
		{
			$config = array(trim($match) => array());
		}
		return $this->adapter->get($config);
	}

	// Borrowed from JDocumentHtml for compatibility
	function countModules($condition)
	{
		jimport('joomla.application.module.helper');

		$result = '';
		$words = explode(' ', $condition);
		for ($i = 0; $i < count($words); $i += 2)
		{
			// odd parts (modules)
			$name = strtolower($words[$i]);
			$words[$i] = ((isset($this->_buffer['modules'][$name])) && ($this->_buffer['modules'][$name] === false)) ? 0
				: count(JModuleHelper::getModules($name));
		}

		$str = 'return ' . implode(' ', $words) . ';';

		return eval($str);
	}

	/**
	 * Reorder main content / sidebars in the order selected by the user
	 */
	private function reorderContent()
	{

		/**
		 * regular patterns to identify every column
		 * Added id to avoid the annoying bug that avoids the user to use HTML5 tags
		 */
		$patterns = array('sidebar1' => '/<aside(.*)id="sidebar1">(.*)<\/aside>/isU', 'sidebar2' => '/<aside(.*)id="sidebar2">(.*)<\/aside>/isU',
			'main' => '/<section(.*)id="main"(.*)>(.*)<\/section>/isU');

		// only this columns
		$allowedColNames = array_keys($patterns);
		$reorderedCols = array();
		$reorderedContent = '';

		// get column configuration
		$columnCfg = $this->document->params->get('columns', 'sidebar1:3;main:6;sidebar2:3');
		$colStrings = explode(';', $columnCfg);
		if ($colStrings)
		{
			foreach ($colStrings as $colString)
			{
				list($colName, $colWidth) = explode(':', $colString);
				if (in_array($colName, $allowedColNames))
				{
					$reorderedCols[] = $colName;
				}
			}
		}

		// get column contents with regular expressions
		$patternFound = false;
		foreach ($patterns as $column => $pattern)
		{

			// save the content into a variable
			$$column = null;
			if (preg_match($pattern, $this->template, $matches))
			{
				$$column = $matches[0];

				$replacement = '';
				// replace first column found with string '##wricolumns##' to reorder content later
				if (!$patternFound)
				{
					$replacement = '##wricolumns##';
					$patternFound = true;
				}
				$this->template = preg_replace($pattern, $replacement, $this->template);
			}
		}

		// if columns reordered and column content found replace contents
		if ($reorderedCols && $patternFound)
		{
			foreach ($reorderedCols as $colName)
			{
				if (!is_null($$colName))
				{
					$reorderedContent .= $$colName;
				}
			}
		}

		$this->template = preg_replace('/##wricolumns##/isU', $reorderedContent, $this->template);

		return $reorderedContent;

	}

	public function addJSScript($url)
	{
		$javascriptBottom = ($this->document->params->get('javascriptBottom', 1) == 1 ? true : false);

		if ($javascriptBottom)
		{
			$this->_jsScripts[] = $url;
		}
		else
		{
			$document = JFactory::getDocument();
			$document->addScript($url);
		}
	}

	private function addJSScriptDeclaration($script)
	{
		$javascriptBottom = ($this->document->params->get('javascriptBottom', 1) == 1 ? true : false);

		if ($javascriptBottom)
		{
			$this->_jsDeclarations[] = $script;
		}
		else
		{
			$document = JFactory::getDocument();
			$document->addScriptDeclaration($script);
		}
	}

	public function generateJS()
	{
		$javascriptBottom = ($this->document->params->get('javascriptBottom', 1) == 1 ? true : false);
		if ($javascriptBottom)
		{
			$script = "\n";
			if ($this->_jsScripts)
				foreach ($this->_jsScripts as $js)
				{
					$script .= "<script src='$js' type='text/javascript'></script>\n";
				}
			if ($this->_jsDeclarations)
			{
				$script .= "<script type='text/javascript'>\n";
				foreach ($this->_jsDeclarations as $js)
				{
					$script .= "$js\n";
				}
				$script .= "</script>\n";
			}
			return $script;
		}
		return "";
	}
}
