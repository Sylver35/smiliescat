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

class v110_schema extends migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'smilies', 'category') && $this->db_tools->sql_table_exists($this->table_prefix . 'smilies_category');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v32x\v328');
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
