<?php
/**
 *
 * @package Breizh Shoutbox Extension
 * @copyright (c) 2018-2020 Sylver35  https://breizhcode.com
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace sylver35\smiliescat\migrations;

use phpbb\db\migration\migration;

class smiliescat_1_0_0 extends migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'smilies', 'category') && $this->db_tools->sql_table_exists($this->table_prefix . 'smilies_category');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v32x\v328');
	}

	public function update_data()
	{
		return array(
			// Config
			array('config.add', array('smilies_category_nb', 0)),

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

	public function update_schema()
	{
		return array(
			'add_tables' => array(
				$this->table_prefix . 'smilies_category' => array(
					'COLUMNS'		=> array(
						'cat_lang_id'		=> array('UINT', null, 'auto_increment'),
						'cat_id'			=> array('UINT', 0),
						'cat_order'			=> array('UINT', 0),
						'cat_lang'			=> array('VCHAR:30', ''),
						'cat_name'			=> array('VCHAR:50', ''),
						'cat_title'			=> array('VCHAR:50', ''),
						'cat_nb'			=> array('UINT', 0),
					),
					'PRIMARY_KEY'	=> array('cat_lang_id'),
				),
			),
			'add_columns' => array(
				$this->table_prefix . 'smilies' => array(
					'category'	=> array('UINT', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_tables' => array(
				$this->table_prefix . 'smilies_category',
			),
			'drop_columns' => array(
				$this->table_prefix . 'smilies' => array(
					'category',
				),
			),
		);
	}
}
