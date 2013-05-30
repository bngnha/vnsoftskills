<?php
/**
 * @version		3.2
 * @package		DISQUS Comments for Joomla! (package)
 * @author		JoomlaWorks - http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2012 JoomlaWorks Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */
// no direct access
defined('_JEXEC') or die('Restricted access');
?>
<?php echo $row->text; ?>

<div class="jwDisqusForm">
	<?php echo $output->comments; ?>
	<div id="jwDisqusFormFooter">
		<a target="_blank" href="http://disqus.com" class="dsq-brlink">
			<?php echo JText::_("JW_DISQUS_BLOG_COMMENTS_POWERED_BY"); ?> <span class="logo-disqus">DISQUS</span>
		</a>
		<div class="clr"></div>
	</div>
</div>

<div class="clr"></div>
