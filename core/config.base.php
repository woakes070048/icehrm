<?php
if(!defined('SIGN_IN_ELEMENT_MAPPING_FIELD_NAME')){define('SIGN_IN_ELEMENT_MAPPING_FIELD_NAME','employee');}

if(!defined('APP_NAME')){define('APP_NAME','IceHrm');}
if(!defined('FB_URL')){define('FB_URL', 'https://www.facebook.com/icehrm');};
if(!defined('TWITTER_URL')){define('TWITTER_URL', 'https://twitter.com/icehrmapp');};

if(!defined('HOME_LINK_ADMIN')){
    define('HOME_LINK_ADMIN', CLIENT_BASE_URL . "?g=admin&n=dashboard&m=admin_Admin");
}
if(!defined('HOME_LINK_OTHERS')){
    define('HOME_LINK_OTHERS', CLIENT_BASE_URL . "?g=modules&n=dashboard&m=module_Personal_Information");
}

//Version
define('VERSION', '33.5.0.OS');
define('CACHE_VALUE', '33.5.0.OS.2023-01310520');
define('VERSION_NUMBER', '330500');
define('VERSION_DATE', '02/11/2024');

if(!defined('CONTACT_EMAIL')){define('CONTACT_EMAIL','icehrm@gamonoid.com');}
if(!defined('KEY_PREFIX')){define('KEY_PREFIX','IceHrm');}

define('UI_SHOW_SWITCH_PROFILE', true);
define('CRON_LOG', ini_get('error_log'));

define('MEMCACHE_HOST', '127.0.0.1');
define('MEMCACHE_PORT', '11211');

if(!defined('WK_HTML_PATH')){
    define('WK_HTML_PATH', '/usr/bin/xvfb-run -- /usr/local/bin/wkhtmltopdf');
}
define('ALL_CLIENT_BASE_PATH', '/var/www/icehrm.app/icehrmapp/');

define('IS_CLOUD', false);
define('LDAP_ENABLED', true);
define('SAML_ENABLED', true);
if(!defined('APP_WEB_URL')) {define('APP_WEB_URL', 'https://icehrm.com');}

if (!defined('EXTENSIONS_URL')) {
    define('EXTENSIONS_URL', str_replace('/web/', '/extensions/', BASE_URL));
}
