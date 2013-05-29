<?php
/**------------------------------------------------------------------------
; plg_soccomments - Social Comments Plugin Content for Joomla 1.7 and Joomla 2.5
; author    tallib
;copyright - Copyright (C) 2011 FreeJoom. All Rights Reserved.
; @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
; Websites: http://FreeJoom.ru
; Technical Support:  FreeJoom- http://freejoom.ru/soccomments.html
; -------------------------------------------------------------------------------------------
/**------------------------------------------------------------------------
* file: soccomments.php 1.3.0, March 2012 14:00:00 tallib $
; package:	SocComments Plugin Content
------------------------------------------------------------------------**/
//No direct access!
defined( '_JEXEC' ) or die;

class plgContentSocComments extends JPlugin 
{
	public function onContentAfterDisplay($context, &$row, &$params, $page = 0)
	{	
		if($context == 'com_content.article'){
			//hide plugin on category
			$exclusion_categories = $this->params->get('sc_categories', 0);
			if(!empty($exclusion_categories)){
				if(strlen(array_search($row->catid, $exclusion_categories))){
					return false;
				}
			}
						
			//hide plugin on article 
			$exclusion_articles = $this->params->get('sc_articles', '');
			$articlesArray = explode(",",$exclusion_articles);
			if(!empty($exclusion_articles)){
				if(strlen(array_search($row->id, $articlesArray))){
					return false;
				}
			}
			//plugin
			require_once(JPATH_BASE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php');
			$comments = JPATH_BASE . '/components/com_jcomments/jcomments.php';
            if (file_exists($comments)) {require_once($comments);}
            $id = JRequest::getInt('id');
			$Itemid = JRequest::getVar("Itemid","1");
			if($row->id){
				$link = JRoute::_(ContentHelperRoute::getArticleRoute($row->id, $row->catid));
				$jURI = & JURI::getInstance();
				$link = $jURI->getScheme()."://".$jURI->getHost().$link;
			}else{
				$jURI =& JURI::getInstance();
				$link = $jURI->toString();
			}
			
			$document = & JFactory::getDocument();
		    $config =& JFactory::getConfig();
			$document->addStyleSheet('plugins/content/soccomments/soc/css/soccomments.css');
			$document->addScript("http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js");
			$document->addScript("plugins/content/soccomments/soc/js/jquery-ui-1.8.16.custom.min.js");
            $document->addScript("plugins/content/soccomments/soc/js/jsoccomments.js");

			//setting css 
			
			//code plugin
			$html  = '';
			$fbb  = '';
			$jcb  = '';
			$vkb  = '';
			if($this->params->get('sc_show_text_icon')==1){
			$fbi  = '<img src="plugins/content/soccomments/soc/css/facebook.png" width="12" height="12">';
			$vki  = '<img src="plugins/content/soccomments/soc/css/vkontakte.png" width="12" height="12">';
			if($this->params->get('comment_system')=='jcomments'){
			$jci  = '<img src="plugins/content/soccomments/soc/css/chat.png" width="13" height="13">';
			} else {$jci  = '<img src="plugins/content/soccomments/soc/css/disqus.png" width="12" height="12">';}
			} else {
			$fbi  = '';
			$jci  = '';
			$vki  = '';
			}
			$doc = &JFactory::getDocument();
			$vk_text = $this->params->def('vk_text');
			$fb_text = $this->params->def('fb_text');
			$jc_text = $this->params->def('jc_text');
			$cpr = $this->params->def('cpr');
			$sc_vkl = $this->params->def('sc_vkl');
			$sc_text_com = $this->params->def('sc_text_com');
			$sc_font_com = $this->params->def('sc_font_com');
            if($this->params->get('FacebookComments')==1){
			$fbb .= '<li style="font-size:'.$sc_vkl.'px;"><a href="#tabs-1" title="'.$fb_text.'" >'.$fbi.' '.$fb_text.'</a></li>';} else {$fbb .='';}
			if($this->params->get('Vk')==1){
			$vkb .= '<li style="font-size:'.$sc_vkl.'px;"><a href="#tabs-2" title="'.$vk_text.'">'.$vki.' '.$vk_text.'</a></li>';} else {$vkb .='';}
			if($this->params->get('Jc')==1){
			$jcb .= '<li style="font-size:'.$sc_vkl.'px;"><a href="#tabs-3" title="'.$jc_text.'">'.$jci.' '.$jc_text.'</a></li>';} else {$jcb .='';}
			if($this->params->get('sc_show_text_com')==1){
			$html .= '<p><span style="font-size:'.$sc_font_com.'px;font-weight:bold;">'.$sc_text_com.'</span></p>';
			}
			$html .= '<div id="tabs"><ul>';
			if ($this->params->get('jc_order')==1 && $this->params->get('fb_order')==2){
			$html .= ''.$jcb.''.$fbb.''.$vkb.'';}
			elseif ($this->params->get('jc_order')==1 && $this->params->get('vk_order')==2){
			$html .= ''.$jcb.''.$vkb.''.$fbb.'';}
			elseif ($this->params->get('fb_order')==1 && $this->params->get('jc_order')==2){
			$html .= ''.$fbb.''.$jcb.''.$vkb.'';}
			elseif ($this->params->get('fb_order')==1 && $this->params->get('vk_order')==2){
			$html .= ''.$fbb.''.$vkb.''.$jcb.'';}			
			elseif ($this->params->get('vk_order')==1 && $this->params->get('fb_order')==2){
			$html .= ''.$vkb.''.$fbb.''.$jcb.'';}
			elseif ($this->params->get('vk_order')==1 && $this->params->get('jc_order')==2){
			$html .= ''.$vkb.''.$jcb.''.$fbb.'';}
			else $html .= '<h3>Incorrect order of tabs</h3>';
			$html .= '</ul>';

			if($this->params->get('FacebookComments')==1){
			$doc = &JFactory::getDocument();
			$fb_id = $this->params->def('fb_id');
			$fbcomments_width = $this->params->def('fbcomments_width');
			$fb_num = $this->params->def('fb_num');
			$fb_admin_id = $this->params->def('fb_admin_id');
			$fbcomments_color = $this->params->def('fbcomments_color');
			$fbcomments_lang = $this->params->def('fbcomments_lang');
			if($this->params->get('fb_show_og_tag')==1){
			$document->addCustomTag('<meta property="fb:admins" content="'.$fb_admin_id.'"/>');
			$document->addCustomTag('<meta property="fb:app_id" content="'.$fb_id.'"/>');
			$document->addCustomTag('<meta property="og:title" content="'.$row->title.'"/>');
			$document->addCustomTag('<meta property="og:type" content="article"/>');
			$document->addCustomTag('<meta property="og:url" content="'.$link.'"/>');
			$document->addCustomTag('<meta property="og:site_name" content="'.$config->getValue('sitename').'"/>');
		    $pattern = "/<img[^>]*src\=['\"]?(([^>]*)(jpg|gif|png|jpeg))['\"]?/";
			preg_match($pattern, $row->text, $matches);
			if(!empty($matches)){
				$document->addCustomTag('<meta property="og:image" content="'.$root.'/'.$matches[1].'"/>');
			}
			}
			$html .='<div  id="tabs-1">';
			$html .='<div id="fb-root"></div>';
			$html .='<script src="http://connect.facebook.net/'.$fbcomments_lang.'/all.js#xfbml=1"></script><fb:comments href="'.$link.'" num_posts="'.$fb_num.'" width="'.$fbcomments_width.'" colorscheme="'.$fbcomments_color.'">';
			$html .='</fb:comments>';
			$html .='</div>';
			}else{
				$html .='';
			}
			
			if($this->params->get('Vk')==1){
			$doc = &JFactory::getDocument();
			$vk_id = $this->params->def('vk_id');
			$vk_width = $this->params->def('vk_width');
			$vk_num = $this->params->def('vk_num');
			$vk_attach = $this->params->def('vk_attach');
			$vk_pub = $this->params->def('vk_pub');
			$html .='<div id="tabs-2">';
			$html .='<script type="text/javascript" src="http://userapi.com/js/api/openapi.js?34"></script><script type="text/javascript">VK.init({apiId: '.$vk_id.', onlyWidgets: true});</script>';
			$html .='<div id="vk_comments"></div>';
			$html .='<script type="text/javascript">';
			$html .='VK.Widgets.Comments("vk_comments", {limit: '.$vk_num.', width: "'.$vk_width.'", attach: "'.$vk_attach.'", autoPublish: '.$vk_pub.'});';
			$html .='</script>';
			$html .='</div>';
			}else{
				$html .='';
			}
			
			$title  = '';
			if($this->params->get('Jc')==1){
			if($this->params->get('comment_system')=='jcomments'){
			$html .='<div id="tabs-3">';
  			$html .= JComments::show($row->id, 'com_content', $row->title);
			$html .='</div>';
			} else {
			$disqus_subdomain = '';
			$disqus_lang = '';
			$disqus_subdomain = $this->params->def('disqus_subdomain');
			$disqus_lang = $this->params->def('disqus_lang');
			$html .='<div id="tabs-3">';
  			$html .= '<div id="disqus_thread"></div><script type="text/javascript">var disqus_shortname = \''.$disqus_subdomain.'\';
	var disqus_config = function () {
	  this.language = "'.$disqus_lang.'";
	};			
           (function() {
        var dsq = document.createElement(\'script\'); dsq.type = \'text/javascript\'; dsq.async = true;
        dsq.src = \'http:\/\/\' + disqus_shortname + \'.disqus.com\/embed.js\';
        (document.getElementsByTagName(\'head\')[0] || document.getElementsByTagName(\'body\')[0]).appendChild(dsq);})();
</script>
<noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
<a href="http://disqus.com" class="dsq-brlink" rel="nofollow">blog comments powered by <span class="logo-disqus">Disqus</span></a>';
			$html .='</div>';
			}
			}else{
				$html .='';
			}
			
			$html .= '</div><div style="clear:both;"></div>';
			$html .= '<div class="'.$cpr.'"><a href="http://socext.com/download/soccomments.html" title="Download SocComments v1.3" target="_blank">Download SocComments v1.3</a></div>';
			$html .= '<div style="clear:both;"></div>';
			//end plugin
			
			//show plugin
			
			//var_dump($row->text);
			//var_dump($row->fulltext);
			//var_dump($row->introtext);
			
			$row->text .= $html;
				//$row->introtext .= $html;
			
		}
		return; 
	}
}
?>