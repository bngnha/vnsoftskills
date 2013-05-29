<?php


// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );
jimport( 'joomla.html.parameter' );

class  plgSystemSocComments extends JPlugin
{

	var $_cache = null;

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @access	protected
	 * @param	object	$subject The object to observe
	 * @param 	array   $config  An array that holds the plugin configuration
	 * @since	1.0
	 */
	function plgSystemSocComments(& $subject, $config)
	{
		parent::__construct($subject, $config);

		//Set the language in the class
		$config =& JFactory::getConfig();
		if (JPluginHelper::isEnabled('system','cache'))
		{
			$plugin = JPluginHelper::getPlugin('system','cache');
			$params = new JParameter($plugin->params);
			$cachetime = $params->get('cachetime', 15);
			$browsercache = $params->get('browsercache', false);
			
		} else {
			$cachetime = 15;
			$browsercache = false;
			
		}
		
		$options = array(
			'cachebase' 	=> JPATH_BASE.DS.'cache',
			'defaultgroup' 	=> 'page',
			'lifetime' 		=> $cachetime * 60,
			'browsercache'	=> $browsercache,
			'caching'		=> false,
			'language'		=> $config->getValue('config.language', 'en-GB')
		);

		jimport('joomla.cache.cache');
		$this->_cache =& JCache::getInstance( 'page', $options );
		
	}

	/**
	* Converting the site URL to fit to the HTTP request
	*
	*/
	function onAfterRender() {
			global $_PROFILER;
			$app = & JFactory::getApplication();
			$dispatcher = & JDispatcher::getInstance();
			if($app->isAdmin() || JDEBUG) {
				return;
			}	
			$option = JRequest::getVar('option' , '');
			$view = JRequest::getVar('view' , '');
			if (!JPluginHelper::isEnabled('content','soccomments')) return true;
			$search = $this->params->get('needle','{soccomments}');
			//$imgwidth = $this->params->get('width' , 150);
			if ($search == '') return true;
			$data = JResponse::toString($app->getCfg('gzip'));
			if (strpos($data, $search) === false) return false;
			$row = new stdClass();
			$row->text = '';
			//$match = array();
			//preg_match('/<title>([^<]*)<\/title>/si',$data,$match);
			//$row->title = $match[1];
			JRequest::setVar('view' , 'row');
			$temp = JPluginHelper::getPlugin('content','soccomments');
			require_once (JPATH_SITE . DS . 'plugins' . DS . 'content'. DS . 'soccomments' . DS . 'soccomments.php');
			$plgSocComments = new plgContentSocComments($dispatcher , array('params' => new JParameter($temp->params)));
			$plgSocComments->onContentAfterDisplay('com_content.article', $row , $par10 = null , $par2 = null);
			
			
			if($plgSocComments->params->get('comment_system')=='jcomments'){
			$jt = $plgSocComments->params->get('jc_text');
				$row->text  = str_replace($jt, '', $row->text );
				$row->text  = str_replace('chat.png', 'secret.png', $row->text );
				$row->text  = str_replace('#tabs-3', '#', $row->text );
				}
			
			
			if($plgSocComments->params->get('fb_show_og_tag')==1) {
                    $current=JFactory::getURI();
					$link = $current->toString();
					$config =& JFactory::getConfig();
					preg_match('/<title>([^<]*)<\/title>/si',$data,$match);
					$title = $match[1];
					$pos = strpos($data,'</head>');
					$start = substr($data,0,$pos);
					$end = substr($data,$pos+7);
					
					$meta = '<meta property="og:title" content="'.$title.'"/>'."\n";
					
					if ($option == 'com_content')
						$meta .= '<meta property="og:type" content="article"/>'."\n";
						
					$meta .= '<meta property="og:url" content="'.$link.'"/>'."\n";
					$meta .= '<meta property="og:site_name" content="'.$config->getValue('sitename').'"/>'."\n";
				
					$pattern = "/<img[^>]*src\=['\"]?(([^>]*)(jpg|gif|png|jpeg))['\"]?[^>]*>/i";
					$matches = array();
					if(preg_match_all($pattern, $data, $matches)){
						foreach ($matches[0] as $key => $val) {
							$width = array();
							if ((strpos($matches[1][$key], '/templates/') === FALSE) AND preg_match("/width\=\"([\d]+)\"/i", $val , $width) AND ($width[1] > $imgwidth)) { 
								$meta .= '<meta property="og:image" content="'.$matches[1][$key].'"/>'."\n";
								break;
							}
						}
					}
			}
			$data = str_replace($search,  $row->text , $data);
			if(JDEBUG)
			{
				$_PROFILER->mark('arterSystemSocComments');
				echo implode( '', $_PROFILER->getBuffer());
			}			
			JResponse::setBody($data);
	}
		function onAfterRoute()
		{
			$mainframe = & JFactory::getApplication('site');
			$mainframe->getRouter();
			$document = & JFactory::getDocument();
			$document->addStyleSheet('plugins/content/soccomments/soc/css/soccomments.css');
			$document->addScript("http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js");
			$document->addScript("plugins/content/soccomments/soc/js/jquery-ui-1.8.16.custom.min.js");
            $document->addScript("plugins/content/soccomments/soc/js/jsoccomments.js");		
		}
}
