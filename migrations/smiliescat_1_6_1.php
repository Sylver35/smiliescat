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

class smiliescat_1_6_1 extends migration
{
	public function effectively_installed()
	{
		return isset($this->config['smilies_first_cat']);
	}

	static public function depends_on()
	{
		return ['\sylver35\smiliescat\migrations\smiliescat_1_6_0'];
	}

	public function update_data()
	{
		return [
			// Config
			['config.add', ['smilies_first_cat', $this->get_first_order()]],
		];
	}

	public function get_first_order()
	{
		// Get first order id...
		$sql = 'SELECT cat_order, cat_id
			FROM ' . $this->table_prefix . 'smilies_category
			ORDER BY cat_order ASC';
		$result = $this->db->sql_query_limit($sql, 1);
		$first = (int) $this->db->sql_fetchfield('cat_id');
		$this->db->sql_freeresult($result);

		return $first;
	}
}
