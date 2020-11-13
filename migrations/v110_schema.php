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
		return ['\phpbb\db\migration\data\v32x\v328'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'smilies_category' => [
					'COLUMNS'		=> [
						'cat_lang_id'		=> ['UINT', null, 'auto_increment'],
						'cat_id'			=> ['UINT', 0],
						'cat_order'			=> ['UINT', 0],
						'cat_lang'			=> ['VCHAR:30', ''],
						'cat_name'			=> ['VCHAR:50', ''],
						'cat_title'			=> ['VCHAR:50', ''],
						'cat_nb'			=> ['UINT', 0],
					],
					'PRIMARY_KEY'	=> ['cat_lang_id'],
				],
			],
			'add_columns' => [
				$this->table_prefix . 'smilies' => [
					'category'	=> ['UINT', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'smilies_category',
			],
			'drop_columns' => [
				$this->table_prefix . 'smilies' => [
					'category',
				],
			],
		];
	}
}
