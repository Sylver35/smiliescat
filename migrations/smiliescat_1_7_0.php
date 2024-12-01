<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020-2024 Sylver35  https://breizhcode.com
* @license		https://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\migrations;

use phpbb\db\migration\migration;

class smiliescat_1_7_0 extends migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'smilies', 'display_on_cat');
	}

	static public function depends_on()
	{
		return ['\sylver35\smiliescat\migrations\smiliescat_1_6_1'];
	}

	public function update_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'smilies' => [
					'category' => ['UINT', '9998'],
				],
			],
			'add_columns' => [
				$this->table_prefix . 'smilies' => [
					'display_on_cat'	=> ['UINT', 1],
				],
			],
			'add_index' => [
				$this->table_prefix . 'smilies' => [
					'display_on_cat'	=> ['display_on_cat'],
					'category'			=> ['category'],
				],
			],
		];
	}

	public function update_data()
	{
		return [
			['custom', [
				[&$this, 'update_display_on_cat']
			]],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'smilies' => [
					'display_on_cat',
				],
			],
		];
	}

	public function update_display_on_cat()
	{
		$this->db->sql_query('UPDATE ' . $this->table_prefix . 'smilies SET category = 9998 WHERE category = 0');
	}
}
