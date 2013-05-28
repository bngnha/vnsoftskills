<?php

class WrightAdapterJoomlaFooter
{
	public function render($args)
	{
		$doc = Wright::getInstance();
		$js = $doc->generateJS();

		if ($doc->document->params->get('rebrand', 'no') !== 'yes')
		{
			return '<a class="copyright" href="http://www.vnsoftskills.com/">© Copyright 2013 VnSoftSkills.com, All rights reserved.</a>' . $js;
		}
		else
		{
			return $js;
		}
		
		
	}
}
