var $soc = jQuery.noConflict();
$soc(function(){
	
				// Tabs
				$soc('#tabs').tabs();
				
				//hover states on the static widgets
				$soc('#dialog_link, ul#icons li').hover(
					function() { $soc(this).addClass('ui-state-hover'); }, 
					function() { $soc(this).removeClass('ui-state-hover'); }
				);
				
			});