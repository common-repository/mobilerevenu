<?php
require_once('../../../wp-config.php');
require_once('includes/_mobilerevenu_plugin.class.php');
if (!isset($_GET['post'])):
	header("Content-type: text/javascript");
?>
(function(){
	tinymce.PluginManager.requireLangPack('mobilerevenu');
	tinymce.create('tinymce.plugins.mobilerevenu', {
		getInfo : function() {
			return {
				longname  : 'MobileRevenu Configurator',
				author    : 'MobileRevenu',
				authorurl : 'http://www.mobilerevenu.com',
				infourl   : 'http://www.mobilerevenu.com',
				version   : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		},
		init: function(ed , url ) {
			ed.addCommand('mcemobilerevenu',function(){
				var template = {};
				try {
					var post_ID = document.getElementsByName('temp_ID')[0].value;
				} catch (e) {
					var post_ID = document.getElementsByName('post_ID')[0].value;
				}
				template['file']   = '<?php echo WP_PLUGIN_URL; ?>/mobilerevenu/tinymce.php?post='+post_ID;
				template['width']  = 460;
				template['height'] = 200;
				template['inline'] = 1;
				args = { resizable:'yes', scrollbars:'no', inline : 'yes' };
				ed.windowManager.open( template , args );
			});
			ed.addButton('mobilerevenu',{
				title: 'MobileRevenu Configurator',
				image: '<?php echo WP_PLUGIN_URL; ?>/mobilerevenu/img/button.png',
				cmd:'mcemobilerevenu'
				});
		},
		createControl:function(n,cm) {
			return null;
		}
	});
	tinymce.PluginManager.add('mobilerevenu',tinymce.plugins.mobilerevenu);
})();
<?php 
else:
	$plugin_info=$MR->get_info('plugin_info');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<title><?php echo $plugin_info['nicename'];?></title>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script src="<?php bloginfo('siteurl'); ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
<?php $MR->wp_admin_head(); ?>
<script>webmaster.callback=function(promolink){ $('#url').val(promolink); };</script>
<script src="//media.mobilerevenu.com/js/tools/configurator.js"></script>
<script>
var MobileRevenuDialog = {
	local_ed : 'ed',
	init : function(ed) {MobileRevenuDialog.local_ed=ed;tinyMCEPopup.resizeToInnerSize();},
	insert : function insertMobileRevenuSection(ed) {
		var content = MobileRevenuDialog.local_ed.selection.getContent();
		if (content) output='<a href="'+$('#url').val()+'">'+content+'</a>';
		else output=$('#url').val();
		tinyMCEPopup.execCommand('mceReplaceContent',false,output);
		tinyMCEPopup.close();
	}
};
tinyMCEPopup.onInit.add(MobileRevenuDialog.init,MobileRevenuDialog);
document.write('<base href="'+tinymce.baseURL+'" />');
</script>
</head>
<body>
<div align="center" id="mobilerevenu-dialog">
	<p class="title"><?php _e('Link Configuration',$plugin_info['name']); ?></p>
		<form action="/" method="get" accept-charset="utf-8">
		<table>
			<tr valign="top">
				<td>
					<select size="1" name="mr_syn" id="synergie" data-selected="<?php echo $MR->get('syn');?>"></select>
					<select size="1" name="mr_niche" id="niche" data-selected="<?php echo (int)$MR->get('niche');?>"></select>
				</td>
			</tr>
		</table>
		<p><a href="javascript:MobileRevenuDialog.insert(MobileRevenuDialog.local_ed)"><?php _e('Insert your link',$plugin_info['name']);?></a></p>
		<input type="hidden" name="url" id="url" value="">
		</form>
</div>
</body>
</html>
<?php endif; ?>
