<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020 Sylver35  https://breizhcode.com
* @license		http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\migrations;

use phpbb\db\migration\migration;

class v110_data extends migration
{
	public function effectively_installed()
	{
		return isset($this->config['smilies_category_nb']);
	}

	static public function depends_on()
	{
		return array(
			'\sylver35\smiliescat\migrations\v110_schema',
		);
	}

	public function update_data()
	{
		return array(
			// Config
			array('config.add', array('smilies_category_nb', 0)),
			array('config.add', array('smilies_per_page_cat', 20)),

			// Add ACP modules
			array('module.add', array('acp', 'ACP_MESSAGES', array(
				'module_basename'	=> '\sylver35\smiliescat\acp\main_module',
				'module_langname'	=> 'ACP_SC_SMILIES',
				'module_mode'		=> 'smilies',
				'module_auth'		=> 'ext_sylver35/smiliescat && acl_a_icons',
			))),
			array('module.add', array('acp', 'ACP_MESSAGES', array(
				'module_basename'	=> '\sylver35\smiliescat\acp\main_module',
				'module_langname'	=> 'ACP_SC_CONFIG',
				'module_mode'		=> 'config',
				'module_auth'		=> 'ext_sylver35/smiliescat && acl_a_icons',
			))),
		);
	}
}
