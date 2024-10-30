<?php
if (!defined('WP_CONTENT_URL'))  define('WP_CONTENT_URL', WP_SITEURL.'/wp-content' );
if (!defined('WP_CONTENT_DIR'))  define('WP_CONTENT_DIR', ABSPATH.'wp-content' );
if (!defined('WP_PLUGIN_URL'))   define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins' );
if (!defined('WP_PLUGIN_DIR'))   define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins' );
include_once('mdetect.php');
if (!class_exists('MobileRevenuPlugin',false)):
class MobileRevenuPlugin  {
	private $_options = array(
			'mr_id'			=> array('webmaster ID','0'),
			'mr_syn'		=> array('Website classification','hetero'),
			'mr_niche'		=> array('Website classification','0'),
			'mr_mb'			=> array('White label ID','0'),
			'mr_tracker'		=> array('Tracking',''),
			'mr_key'		=> array('Your key',''),
			'mr_redir'		=> array('Mobile Redirect','0'),
			'mr_vioc'		=> array('Old generation devices','0'),
			'mr_theme' => array('Mobile Theme',''),
			'mr_gtw'			=> array('Gateway','http://m.mobilerevenu.com')
			),
			$_infos = array('lang','redirect_link','plugin_info'),
			$_lang = array(
				'wordpress'	=> WPLANG, 'browser'	=> null,
				'request'	=> null, 'current'	=> null,
				'mo'		=> null, 'locale'	=> null
			),
			$_ini = array(
				'MOBILE'=>array('extra'=>null,'blacklist'=>null),
				'VIOC'=>array('blacklist'=>null,'extra'=>null),
				'SMARTPHONE'=>array('only'=>null,'blacklist'=>null)
			);

	public $mdetect = null;
	public static $lang	= 'en',
		$is_mobile = FALSE,
		$is_mobile_vioc = FALSE,
		$ini_file=null,
		$plugin	= 'mobilerevenu/mobilerevenu.php',
		$plugin_name = 'mobilerevenu',
		$plugin_nicename = 'MobileRevenu',
		$http_accepted	= array('VND.WAP.WML','XHTML+XML','*/*'),
		$agent_denied  = array('OfficeLiveConnector','Swapper'),
		$agent_accepted = array(
			'airness','alcatel','amoi','android',/*'asus',*/
			'benq',/*'bird',*/'blackberry','cdm-','docomo',
			'ericsson',/*'eten',*/'gigabyte','Google Wireless Transcoder',
			/*'haier',*/'Huawei','htc_','htc-','i-mate','i-mobile','ipaq',
			'ipad','ipod','iphone','j-phone','kwc-','kddi','lenovo-',/*'lg',*/ 
			'lg-','lg/','lge-','midp','mitsu/','mot-',/*'motor',*/'motorola', 
			'nokia',/*'nec',*/'palm','panasonic','pantech','philips', 
			/*'ppc',*/'pg-','portalmmm','qtek','sagem','samsung', 
			'sanyo','sch-','sec-','sendo','sgh-','sharp','sie-', 
			'siemens','sonyericsson','sph-','symbianos','treo',
			'telit','toshiba',/*'tsm',*/'vk-','vodafone','wap2',
			/*'wap',*/'windows ce','wnd','wonu','zte-'
		),
		$regex = array(
			'http_accepted'=>null,
			'agent_denied'=>null,
			'agent_accepted'=>null			
		);

	const PREFIX = 'MR_WORDPRESS_DATA',
		URL_REDIR = 'http://m.mobilerevenu.com',
		WEBSERVICE = 'http://webservices.sv3.biz/mobilerevenu.php';

	public function __construct() {
		foreach($this->_options as $k => $option) {
			if (!get_option($k)) update_option($k,$option[1]);
		}
		self::$regex['agent_accepted']='/('.str_replace(',','|',preg_quote(implode(',',self::$agent_accepted),'/')).')/i';
		self::$regex['http_accepted']='/('.str_replace(',','|',preg_quote(implode(',',self::$http_accepted),'/')).')/i';
		self::$regex['agent_denied']='/('.str_replace(',','|',preg_quote(implode(',',self::$agent_denied),'/')).')/i';
		self::init_mobile_check();
		self::init_language();
		self::$lang = $this->_lang['current'];
		
		$ini_list=array("my".self::$plugin_name,self::$plugin_name);
		$extra_config=false;
		foreach($ini_list AS $name) {
			self::$ini_file=WP_PLUGIN_DIR."/".self::$plugin_name."/".$name.".ini";
			$extra_config=@parse_ini_file(self::$ini_file,true);
			if ($extra_config) break;
		}
		if (is_array($extra_config)):
		foreach($this->_ini as $k=>$ini) {
			foreach($ini AS $kk=>$v) {
				if (isset($extra_config[$k][$kk])) $this->_ini[$k][$kk]=$extra_config[$k][$kk];
			}
		}
		endif;
		if (!headers_sent()):
			$header=array('lang'=>array('wp'=>$this->_lang['wordpress'],'current'=>self::$lang),
				'is_mobile'=>self::$is_mobile,'is_vioc'=>self::$is_mobile_vioc,
				'redir'=>get_option('mr_redir'),'vioc'=>get_option('mr_vioc')
				);
			$header_content=str_replace(array('{','}','"',','),array('(',')','','; '),json_encode($header));
			header("X-MobileRevenu: ".$header_content);
		endif;
		register_activation_hook(self::$plugin, array(&$this,'install'));
		register_deactivation_hook(self::$plugin, array(&$this,'uninstall'));
	}

	static public function init_version() {
		if (!isset($GLOBALS[self::PREFIX]['version'])):	
			$plugin_file = dirname(__FILE__).'/../'.basename(self::$plugin);
			if (!function_exists('get_plugin_data')):
				if (file_exists(ABSPATH.'wp-admin/includes/plugin.php')):
					require_once(ABSPATH.'wp-admin/includes/plugin.php'); // 2.3+
				elseif (file_exists(ABSPATH.'wp-admin/admin-functions.php')):
					require_once(ABSPATH.'wp-admin/admin-functions.php'); // 2.1
				else:
					return "0";
				endif;
			endif;
			$data = get_plugin_data($plugin_file, false, false);
			$GLOBALS[self::PREFIX]['version'] = $data['Version'];
		endif;
		return $GLOBALS[self::PREFIX]['version'];
	}
	
	private function init_language() {
		$lang=$this->_lang['wordpress'];
		if (($i=strpos($lang,"-"))!=0) $lang=substr($lang,0,$i)."_".strtoupper(substr($lang,$i+1));
		if (!preg_match('/_/i',$lang)) $lang.='_XX';
		list($langue,$country)=explode('_',$lang,2);
		$this->_lang['wordpress']=$langue;
		$locale = $this->_get_acceptedlocale(NULL);
		if (!empty($locale)) $this->_switch_locale($locale);
	}
	
	private function init_mobile_check() 
	{
		self::$is_mobile = FALSE;
		self::$is_mobile_vioc = FALSE;
		if (isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])) {
			$_SERVER['HTTP_USER_AGENT']=$_SERVER['HTTP_X_OPERAMINI_PHONE_UA'];
		}
		if (empty($_SERVER['HTTP_USER_AGENT'])) return;
		$uA = $_SERVER['HTTP_USER_AGENT'];
		
		$this->mdetect = new uagent_info();
		if ($this->mdetect->isTierGenericMobile || $this->mdetect->isTierRichCss) {
			self::$is_mobile = TRUE;
			self::$is_mobile_vioc = TRUE;
		}
		
		if ($this->_ini['MOBILE']['extra']):
			if (preg_match('/'.$this->_ini['MOBILE']['extra'].'/i',$uA)) self::$is_mobile = TRUE;
		endif;
		if ($this->_ini['MOBILE']['blacklist']):
			if (preg_match('/'.$this->_ini['MOBILE']['blacklist'].'/i',$uA)):
				self::$is_mobile = FALSE;
				self::$is_mobile_vioc = FALSE;
				return;
			endif;
		endif;
		
		if ($this->_ini['VIOC']['extra']):
			if (preg_match('/'.$this->_ini['VIOC']['extra'].'/i',$uA)):
				self::$is_mobile = TRUE;
				self::$is_mobile_vioc = TRUE;
			endif;
		endif;
		if ($this->_ini['VIOC']['blacklist']):
			if (preg_match('/'.$this->_ini['VIOC']['blacklist'].'/i',$uA)) self::$is_mobile_vioc = FALSE;
		endif;
		
		if (!self::$is_mobile):
			if (empty($_SERVER['HTTP_ACCEPT']) || empty($_SERVER['HTTP_USER_AGENT'])) return;
			if (!self::check_http_accept($_SERVER['HTTP_ACCEPT'])) return;
			if (!self::check_http_user_agent($_SERVER['HTTP_USER_AGENT'])) return;			
		endif;
		
		self::$is_mobile = TRUE;
		$redir = (int) self::get('redir');
		
		if ($redir==2):
			if ($this->_ini['SMARTPHONE']['only']):
				if (!preg_match('/'.$this->_ini['SMARTPHONE']['only'].'/i',$uA)) self::$is_mobile_vioc = TRUE;
			endif;
			if ($this->_ini['SMARTPHONE']['blacklist']):
				if (preg_match('/'.$this->_ini['SMARTPHONE']['blacklist'].'/i',$uA)) self::$is_mobile_vioc = TRUE;
			endif;
		endif;
	
		if ((int)self::get('vioc')==1 && self::$is_mobile_vioc) $this->mobile_redirect();
		if ($redir==0) return;
		switch($redir) {
			case 2:
				$mobiletheme=get_option('mr_theme');
				if (!$mobiletheme) $this->mobile_redirect();
				add_filter('stylesheet',array(&$this,'theme_switcher'));
				add_filter('template',array(&$this,'theme_switcher'));
			break;
			case 1:
			default:
				$this->mobile_redirect();
			break;
		}
	}
	
	static public function check_http_accept($value=NULL) {
		return preg_match(self::$regex['http_accepted'],$value);
	}
	
	static public function check_http_user_agent($value=NULL) {
		$status = false;
		if (!preg_match(self::$regex['agent_denied'],$value)) {
			$status = preg_match(self::$regex['agent_accepted'],$value);
		}
		return $status;	
	}

	public function install() {
		self::_manage_event('install');
	}

	public function uninstall() {
		self::_manage_event('uninstall');
	}
	
	public function locale_filter($locale) {
		if ($this->_lang['locale'] && !is_admin()) {
			return $this->_lang['locale'];
		}
		return $locale;
	}

	public function get($key,$param=0) {
		$end=($key=='tracker')?'_mrplug':'';
		if ($param==1) $end='';
		$value=get_option('mr_'.$key);
		if ($param==2) return __($this->_options['mr_'.$key][0],self::$plugin_name);
		if (!isset($value)) return $end;
		return $value.$end;
	}

	public function get_info($action='',$param=array()) {
		if (!in_array($action,$this->_infos)) return false;
		$method = new ReflectionMethod($this, '_get_'.$action);
		if (!$method || !$method->isPrivate()) return false;
		return call_user_func(array($this,'_get_'.$action),$param);
	}

	public function mobile_redirect() {
		if (is_admin()) return;
		if (preg_match('/^(wp-login.php|wp-register.php|tinymce.php)$/i',$GLOBALS['pagenow'])) return;	
		if (preg_match('/wp-includes/i',$_SERVER['SCRIPT_NAME'])) return;	
		$link = $this->_get_redirect_link();
		header('Location: '.$link);
		header('Found', true, 302);
		header('Vary: User-Agent');
		header('Content-Type: text/vnd.wap.wml;charset=ISO-8859-1');
?>
<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">
<wml>
	<head>
		<meta http-equiv="cache-control" content="no-cache"/>
		<meta http-equiv="cache-control" content="must-revalidate" />
		<meta http-equiv="cache-control" content="max-age=0" />
		<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	</head>
	<card>
		<onevent type="ontimer">
			<go method="post" href="<?php echo $link; ?>"></go>
		</onevent>
		<timer value="1"/>
	</card>
</wml>
<?php 
		exit;
	}

	public function theme_switcher() {
		$themeList = get_themes();
		$mobiletheme=get_option('mr_theme');
		foreach ($themeList AS $theme) {
			if ($theme['Name']==$mobiletheme) return $theme['Stylesheet'];
		}
	}
	
	public function wp_admin_head() { 
		$version = self::init_version();
		$webmaster=array();
		$webmaster['id']=self::get('id');
		$webmaster['tracker']=self::get('tracker');
		$webmaster['syn']=self::get('syn');
		$webmaster['niche']=(int)self::get('niche');
		$webmaster['mb']=(int)self::get('mb');
		$webmaster['version'] = $version;
		$webmaster['lg']=self::$lang;
		$webmaster['source']='plugin';
		$webmaster['blog']=get_option('siteurl');
		$webmaster['key']=self::get('key');
		$webmaster['txtdefault']=__('Wide target',self::$plugin_name);
		echo '<script>var webmaster='.json_encode($webmaster).'</script>'. PHP_EOL;
		echo '<style href="http://media.mobilerevenu.com/js/tools/configurator_wordpress.css"></style>'. PHP_EOL;
		echo '<script src="http://media.mobilerevenu.com/js/tools/configurator_wordpress.js"></script>'. PHP_EOL;
	}
	
	public function wp_admin_menu() {
		add_theme_page(
			__(self::$plugin_nicename,self::$plugin_name),__(self::$plugin_nicename,self::$plugin_name),
			'edit_themes',self::$plugin_nicename,array(&$this,'wp_admin_panel')
		);
		add_menu_page(
			__(self::$plugin_nicename,self::$plugin_name),__(self::$plugin_nicename,self::$plugin_name),
			'administrator',self::$plugin_nicename,array(&$this,'wp_admin_panel'),
			WP_PLUGIN_URL.'/'.self::$plugin_name.'/img/menu.png'
		);
		if (!isset($_GET['page'])) return;
		if ($_GET['page']==self::$plugin_nicename):
			if (isset($_REQUEST['action'])):
				if ($_REQUEST['action']=='save'):
					foreach($this->_options as $k=>$option) {
						if (!isset($_REQUEST[$k])) continue;
						update_option($k,(string)$_REQUEST[$k]);
					}
					if (isset($_REQUEST['ini_content']) && isset($_REQUEST['file'])):
						$_REQUEST['file']=str_replace(
							'/'.self::$plugin_name.'.ini',
							'/my'.self::$plugin_name.'.ini',$_REQUEST['file']
						);
						self::$ini_file=$_REQUEST['file'];
						@file_put_contents($_REQUEST['file'],stripslashes($_REQUEST['ini_content']));
					endif;
				endif;
			endif;
		endif;
	}
	
	public function wp_admin_mce() {
		if (!current_user_can('edit_posts') || !current_user_can('edit_pages')) {
			return;
		}
		if (get_user_option('rich_editing')=='true') {
			add_filter('mce_external_plugins',array(&$this,'wp_admin_mce_plugin'));
			add_filter('mce_buttons',array(&$this,'wp_admin_mce_button'));
		}
	}
	
	public function wp_admin_mce_plugin($plugin_array) {
		$plugin_array[self::$plugin_name] = WP_PLUGIN_URL.'/'.self::$plugin_name.'/tinymce.php';
		return $plugin_array;
	}
	
	public function wp_admin_mce_button($buttons) {
		array_push($buttons,'|',self::$plugin_name);
		return $buttons;
	}
	
	public function wp_plugin_links($action_links,$plugin_file,$plugin_info) {
		if (!preg_match('/'.self::$plugin_name.'/i',$plugin_info['Name'])) {
			return $action_links;
		}
		$new_action_links = array(
			"<a href='admin.php?page=".self::$plugin_nicename."'>".__('Configuration',self::$plugin_name)."</a>",
			"<a href='admin.php?page=".self::$plugin_nicename."&amp;=2'>".self::get('redir',2)."</a>"
		);
		foreach($action_links as $k=>$action_link) {
			if (!preg_match('/plugin-editor/i',$action_link)) {
				$new_action_links[] = $action_link;
			}
		}
		return $new_action_links;
	}
	
	public function wp_admin_panel() {
		$p=(isset($_REQUEST['p']))?max((int)$_REQUEST['p'],1):1;
		$menu = array(
			array('txt'=>__('Configuration',self::$plugin_name),'p'=>1,'class'=>'nav-tab nav-tab'),
			array('txt'=>__('Redirection',self::$plugin_name),'p'=>2,'class'=>'nav-tab nav-tab'),
			array('txt'=>__('Advanced Settings'),'p'=>3,'class'=>'nav-tab nav-tab'),
			array('txt'=>__('Stats'),'p'=>4,'class'=>'nav-tab nav-tab'),
			array(
				'txt'=>'<img src="http://stats.mobilerevenu.com/images/acces-mobile.gif" style="float:right;border:0">',
				'link'=>'http://stats.mobilerevenu.com',
				'class'=>'nav-tab-external',
				'p'=>0
			)
		);
		echo '<script src="//media.mobilerevenu.com/js/tools/configurator.js"></script>'. PHP_EOL
			. '<div class="wrap">'. PHP_EOL
			. '	<div id="icon-themes" class="icon32"><br /></div>'. PHP_EOL
			. '	<h2 class="nav-tab-wrapper" id="wp_annonce_panel">'. PHP_EOL;
		foreach($menu as $k=>$v) { 
			$class	= ($p==$v['p']) ? $v['class'].'-active':$v['class'];
			$target	= (isset($v['link'])) ? '_blank' : '_self';
			$link	= (isset($v['link'])) ? $v['link'] : '?page='.$_GET['page'].'&p='.$v['p'];
			echo '<a href="'.$link.'" target="'.$target.'" class="'.$class.'">'.$v['txt'].'</a>'. PHP_EOL;
		}
		echo '	</h2>'. PHP_EOL;
?>
<?php if ($p==1): ?>
<form action="" method="post">
	<table class="form-table">
		<tr valign="top">
		<th scope="row"><label for="mr_id"><?php echo self::get('id',2); ?></label></th>
		<td>
			<input name="mr_id" id="id_webmaster" type="text" value="<?php echo self::get('id'); ?>">
			<a href="http://stats.mobilerevenu.com/index.php?lost=1" target="_blank"><?php echo __('Lost ID  ?',self::$plugin_name); ?></a>
			<a href="http://www.mobilerevenu.com" target="_blank"><?php echo __('get your own account',self::$plugin_name); ?></a>
		</td>
		</tr>
		<tr valign="top">
		<th scope="row"><label for="mr_syn"><?php echo self::get('syn', 2); ?></label></th>
		<td>
		<select size="1" name="mr_syn" id="synergie" data-selected="<?php echo self::get('syn'); ?>"></select>
		<select size="1" name="mr_niche" id="niche" data-selected="<?php echo (int) self::get('niche'); ?>"></select>
		</td>
		</tr>
		<tr valign="top">
		<th scope="row"><label for="mr_tracker"><?php echo self::get('tracker', 2); ?></label></th>
		<td><input name="mr_tracker" id="mr_tracker" type="text" value="<?php echo self::get('tracker', 1); ?>"> </td>
		</tr>
		<tr valign="top">
		<th scope="row"><label for="mr_mb"><?php echo self::get('mb', 2); ?></label></th>
		<td><input name="mr_mb" id="mr_mb" type="text" value="<?php echo (int) self::get('mb'); ?>"><a href="http://stats.mobilerevenu.com/?module=webmaster_mobile_mb" target="_blank"><?php echo __('If you have a white label',self::$plugin_name); ?></a></td>
		<tr valign="top">
		<th scope="row"><label for="mr_key"><?php echo self::get('key', 2); ?></label></th>
		<td><input name="mr_key" id="mr_key" type="text" maxlength="12" value="<?php echo self::get('key'); ?>"><a href="http://stats.mobilerevenu.com/?module=webmaster_compte" target="_blank"><?php echo __('Where can I find my personal Key ?',self::$plugin_name); ?></a></td>
		</tr>
	</table>
	<p class="submit">
		<input name="save" type="submit" value="<?php echo __("Confirm your informations",self::$plugin_name); ?>">
		<input type="hidden" name="action" value="save">
	</p>
</form>
<a href="#" target="_blank" style="color:red" id="wp_annonce_config"></a>
<?php elseif ($p==2): ?>
<form method="post">
	<input type="hidden" name="mr_vioc" value="0">
	<p><input type="radio" name="mr_redir" value="0"<?php if (!(int)self::get('redir')): ?> checked="checked"<?php endif; ?>> <?php echo __("<b>Don't redirect</b> my Mobile traffic",self::$plugin_name); ?></p>
	<p><input type="radio" name="mr_redir" value="1" onclick="document.getElementById('vioc').checked='checked';"<?php if (self::get('redir')==1): ?> checked="checked"<?php endif; ?>> <?php echo __("Redirect <u>all</u> my Mobile traffic <b>on MobileRevenu</b>",self::$plugin_name); ?></p>
	<p>
		<input type="checkbox" name="mr_vioc" id="vioc" value="1"<?php if (self::get('vioc')==1): ?> checked="checked"<?php endif; ?>> <?php echo __("Redirect <u>old generation devices</u> <b>on MobileRevenu</b> (recommended)",self::$plugin_name); ?><br>
		<em><?php echo __('No old mobile devices are able to visit your site or its mobile version. We advise you to monetize that traffic with MobileRevenu.<br>MobileRevenu offers an optimized website for those devices. You will earn a commission on each sale made off.',self::$plugin_name); ?></em>
	</p>
	<p><input type="radio" name="mr_redir" value="2"<?php if (self::get('redir')==2): ?> checked="checked"<?php endif; ?> onclick="document.getElementById('vioc').checked='checked';"> <? echo __('Load <u>an other theme</u> if mobile detection: ',self::$plugin_name); ?> 
	<select name="mr_theme" size="1">
<?php
		$themeList = get_themes();
		$mytheme = get_option('mr_theme');
		foreach ($themeList AS $theme) {
			echo "	<option";
			if (!$mytheme && preg_match('/mobilerevenu/i',$theme['Name'])) $mytheme=$theme['Name'];
			if ($mytheme==$theme['Name']) echo ' selected="selected"';
			echo ">".$theme['Name']."</option>".PHP_EOL;
		} 
?>
	</select>
	</p>
	<p class="submit">
		<input name="save" type="submit" value="<?php echo __('Confirm your informations',self::$plugin_name); ?>">
		<input type="hidden" name="action" value="save">
	</p>
</form>

<?php elseif ($p==3): ?>

<h3><?php echo basename(self::$ini_file); ?></h3>
<form name="template" id="template" action="" method="post">
	<div style="width:100%">
		<textarea cols="70" rows="50" name="ini_content" id="ini_content" tabindex="1" style="width:100%">
<?php
	echo file_get_contents(self::$ini_file);
?>
		</textarea>
		<input type="hidden" name="action" value="save" />
		<input type="hidden" name="file" value="<?php echo self::$ini_file; ?>" />		
	</div>
	<p class="submit">
		<input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Update File');?>" tabindex="2"  />
	</p>
</form>

<?php elseif ($p==4): ?>
	<link rel="stylesheet" href="http://media.mobilerevenu.com/js/tools/graph.min.css">
	<div class="mr_charts">
		<div class="mr_wait"><p>Generating the reports...</p></div>
		<div id="chart_devices" class="chart_container"></div>
		<div id="chart_OS" class="chart_container"></div>
	</div>
	<script src="//www.google.com/jsapi"></script>
	<script src="http://media.mobilerevenu.com/js/tools/graph.min.js"></script>
<?php endif;
	}
	
	private function _get_request_locale($lang) {
		$query=0;
		if (isset($_COOKIE['lang'])) $query=$_COOKIE['lang'];
		if (isset($_GET['lang'])) $query=$_GET['lang'];
		$len=strlen($query);
		if ($len<=3 && $len>=2) {
			$lang=strtolower($query);
			$lang=str_replace(array('uk','gb'),array('en','en'),$lang);
			$this->_lang['request']=$lang;
			if (!headers_sent()) {
				setcookie('lang',$lang,time()+3600,COOKIEPATH,COOKIE_DOMAIN,false);
			}
		}
		return $lang;
	}
	
	private function _get_plugin_info($param) {
		$plugin_info=array();
		$plugin_info['name']		= self::$plugin_name;
		$plugin_info['nicename']	= self::$plugin_nicename;
		$plugin_info['file']		= self::$plugin;
		$plugin_info['version']		= self::init_version();
		return $plugin_info;
	}

	private function _get_acceptedlocale($fallback=WPLANG) {
		$langue=$country=false;
		$accepted = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		if (empty($accepted)) return $fallback;
		$de = "([a-zA-Z]{2})";
		$deDE = "($de(-$de)?)";
		$langblock = "(?<langs>$deDE(,$deDE)*)";
		$weighted = "(?<block>$langblock;q=(?<q>\d\.\d))";
		$regex = "/".$weighted."/";
		$results = array();
		preg_match_all($regex,$accepted,$results,PREG_SET_ORDER);
		$findings = array();
		for($i=0;$i<count($results);$i++) {
			$q=$results[$i]["q"];
			$langs=explode(",",$results[$i]["langs"]);
			$findings[$q]=$langs;
		}
		unset($results,$regex);
		krsort($findings,SORT_NUMERIC);
		foreach($findings as $weighting=>$languages ) {
			foreach($languages as $lang) {
				if (($i=strpos($lang,"-"))!=0) $lang=substr($lang,0,$i)."_".strtoupper(substr($lang,$i+1));
				$hit=$this->_find_locale($lang,null);
				if (!$this->_lang['browser']) {
					if (!preg_match('/_/i',$lang)) $lang.='_';
					list($langue,$country)=explode('_',$lang,2);
					$this->_lang['browser']=$langue;
				}
				if (!empty($hit)) {
					return $hit;
				}
			}
		}
		return $fallback;
	}

	private function _get_locale_path($locale=WPLANG,$type='file') {
		$dir=ABSPATH.'wp-content/plugins/'.self::$plugin_name;
		return ($type=='file')?$dir."/languages/$locale.mo":$dir."/languages/";
	}

	private function _get_base_locale($locale=WPLANG) {
		$lang = $locale;
		$loc = strtolower($locale);
		$index = strpos($loc,'_');
		if ($index>0) $lang=substr($loc,0,$index);
		$lang=$this->_get_request_locale($lang);
		return $lang;
	}

	private function _get_lang(string $param) {
		return ($param)?$this->_lang[$param]:$this->_lang;
	}
	
	private function _get_redirect_link($param='') {
		$link = self::URL_REDIR;
		$link = self::get('gtw');
		$link.='/idw/'.self::get('id');
		$link.='/synergie/'.self::get('syn');
		$link.='/tracker/'.self::get('tracker');
		if (self::get('niche')) {
			$link.='/niche/'.self::get('niche');
		}
		if (self::get('mb')) {
			$link.='/mb/'.self::get('mb');
		}
		return $link;
	}
	
	private function _find_locale($locale,$fallback=WPLANG) {
		if (file_exists($this->_get_locale_path($locale)))	{
			return $locale;
		}
		$baselocale = $this->_get_base_locale($locale);
		$fullmatch = strtolower($baselocale."_".$baselocale);
		$hit = null;
		if ($this->_lang['mo'] === null) {
			$this->_lang['mo'] = array();
			if ($handle=opendir($this->_get_locale_path($locale,'dir'))) {
				while (false!==($file=readdir($handle))) {
					if (substr($file,-3,3)==".mo" && $file!=self::$plugin_name.'.mo') {
						$this->_lang['mo'][] = $file;
						if (substr($file,0,2)!=$baselocale) continue;
						if ($hit==null) $hit=substr($file,0,-3);
						if (strtolower($hit)==$fullmatch) { 
							$hit=substr($file,0,-3); 
							break; 
						}
					}
				}
			}
		} else {
			foreach($this->_lang['mo'] as $file) {
				if (substr($file,0,2)!=$baselocale) continue;
				if ($hit==null) $hit = substr($file,0,-3);
				if (strtolower($hit)==$fullmatch) {	
					$hit=substr($file,0,-3); 
					break; 
				}
			}
		}
		return $hit?$hit:$fallback;
	}
	
	private function _manage_event($action) {
		$param['id']		= get_option('mr_id');
		$param['blog']		= get_option('siteurl');
		$param['source']	= 'plugin';
		$param['action']	= $action;
		$param['lg']		= self::$lang;
		$param['version']	= self::init_version();
		@file_get_contents(self::WEBSERVICE.'?'.http_build_query($param));
	}

	private function _switch_locale($locale=WPLANG) {
		//@putenv("LC_ALL=$locale");
		@setlocale(LC_ALL,$locale,$this->_get_base_locale($locale));
		load_textdomain(self::$plugin_name,$this->_get_locale_path($locale));
		list($lang,$country)=explode('_',$locale,2);
		$this->_lang['current']=$lang;
		$this->_lang['locale']=$locale;
		add_filter('locale',array(&$this,'locale_filter'));
	}

}
endif;
$MR = new MobileRevenuPlugin;
