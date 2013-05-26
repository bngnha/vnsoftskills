<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Fitvids
 *
 * @copyright   Copyright (c) 2013 Joomla Bamboo. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;


/**
 * System plugin to add fitvids.js to a site.
 *
 * @package     Joomla.Plugin
 * @author      Joomla Bamboo <design@joomlabamboo.com>
 * @subpackage  System.Fitvids
 * @version     1.0.5
 * @since       1.5
 */
class PlgSystemFitvids extends JPlugin
{
	public function onAfterRoute()
	{
		// Apply the script for Joomla 1.5
		if (version_compare(JVERSION, '1.6', '<'))
		{
			$this->addScript();
		}
	}

	public function onBeforeRender()
	{
		// Apply the script for Joomla 1.6+
		if (version_compare(JVERSION, '1.6', '>='))
		{
			$this->addScript();
		}
	}

	/**
	 * Method to add the fitvids.js to the document
	 */
	private function addScript()
	{
		// Check that we are in the site application.
		if (JFactory::getApplication()->isAdmin())
		{
			return;
		}

		// Set the variables
		$modbase = JURI::root (true) . '/media/fitvids/';
		$selector = $this->params->get('fitVidSelector');

		$doc = JFactory::getDocument();
		$doc->addScript($modbase . 'jquery.fitvids.min.js');

		$fitvids = '(function($) {';
		$fitvids .= '	$(document).ready(function() {';
		$fitvids .= '		$("'.$selector.'").fitVids();';
		$fitvids .= '	});';
		$fitvids .= '})(jQuery);';
		$doc->addScriptDeclaration($fitvids);
	}
}
