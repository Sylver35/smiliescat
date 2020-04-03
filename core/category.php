<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020 Sylver35  https://breizhcode.com
* @license		http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\core;

use phpbb\cache\driver\driver_interface as cache;
use phpbb\db\driver\driver_interface as db;
use phpbb\user;
use phpbb\language\language;
use phpbb\extension\manager;

class category
{
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\extension\manager "Extension Manager" */
	protected $ext_manager;

	/**
	* The database tables
	*
	* @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(cache $cache, db $db, user $user, language $language, manager $ext_manager, $smilies_category_table)
	{
		$this->cache			= $cache;
		$this->db				= $db;
		$this->user				= $user;
		$this->language			= $language;
		$this->ext_manager		= $ext_manager;
		$this->category_table	= $smilies_category_table;
	}

	public function get_version()
	{
		if (($data = $this->cache->get('_smiliescat_version')) === false)
		{
			$md_manager = $this->ext_manager->create_extension_metadata_manager('sylver35/smiliescat');
			$meta = $md_manager->get_metadata();

			$data = array(
				'version'	=> $meta['version'],
				'homepage'	=> $meta['homepage'],
			);
			// cache for 7 days
			$this->cache->put('_smiliescat_version', $data, 604800);
		}

		return $data;
	}

	public function smilies_count($cat)
	{
		$sql_where = ($cat == -1) ? "code <> ''" : "category = $cat";
		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> 'COUNT(DISTINCT smiley_url) AS smilies_count',
			'FROM'		=> array(SMILIES_TABLE => ''),
			'WHERE'		=> $sql_where,
		));
		$result = $this->db->sql_query($sql);
		$smilies_count = (int) $this->db->sql_fetchfield('smilies_count');
		$this->db->sql_freeresult($result);

		return $smilies_count;
	}

	public function select_categories($cat, $modify = false)
	{
		$lang = $this->user->lang_name;
		$select = '<option>' . $this->language->lang('SC_CATEGORY_SELECT') . '</option>';
		if (!$modify)
		{
			$sel = ($cat == -1) ? ' selected="selected"' : '';
			$select .= '<option value="-1"' . $sel . '>' . $this->language->lang('SC_CATEGORY_ANY') . '</option>';
		}

		$sql = 'SELECT *
			FROM ' . $this->category_table . "
				WHERE cat_lang = '$lang'
				ORDER BY cat_order ASC";
		$result = $this->db->sql_query($sql, 3600);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$selected = ($cat == $row['cat_id']) ? ' selected="selected"' : '';
			$select .= '<option title="' . $row['cat_name'] . '" value="' . $row['cat_id'] . '"' . $selected . '> ' . $row['cat_name'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		$selected_defaut = ($cat == 0) ? ' selected="selected"' : '';
		$select .= '<option title="' . $this->language->lang('SC_CATEGORY_DEFAUT') . '" value="0"' . $selected_defaut . '> ' . $this->language->lang('SC_CATEGORY_DEFAUT') . '</option>';

		return $select;
	}

	public function capitalize($var)
	{
		return $this->db->sql_escape(ucfirst(strtolower(trim($var))));
	}

	public function get_max_order()
	{
		// Get max order id...
		$sql = 'SELECT MAX(cat_order) AS maxi
			FROM ' . $this->category_table;
		$result = $this->db->sql_query($sql, 3600);
		$max = (int) $this->db->sql_fetchfield('maxi', $result);
		$this->db->sql_freeresult($result);

		return $max;
	}

	public function get_max_id()
	{
		// Get max id...
		$sql = 'SELECT MAX(cat_id) AS id_max
			FROM ' . $this->category_table;
		$result = $this->db->sql_query($sql, 3600);
		$id_max = (int) $this->db->sql_fetchfield('id_max', $result);
		$this->db->sql_freeresult($result);

		return $id_max;
	}

	public function get_first_order()
	{
		// Get first order id...
		$sql = 'SELECT cat_order, cat_id
			FROM ' . $this->category_table . '
			ORDER BY cat_order ASC';
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$mini = $row['cat_id'];
		$this->db->sql_freeresult($result);

		return $mini;
	}

	public function shoutbox_smilies($event)
	{
		$i = $cat_order = $cat_id = 0;
		$list_cat = [];
		$lang = $this->user->lang_name;

		$sql = $this->db->sql_build_query('SELECT', array(
			'SELECT'	=> '*',
			'FROM'		=> array($this->category_table => ''),
			'WHERE'		=> "cat_lang = '$lang'",
			'ORDER_BY'	=> 'cat_order ASC',
		));
		$result = $this->db->sql_query($sql, 3600);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$list_cat[$i] = array(
				'cat_id'		=> $row['cat_id'],
				'cat_order'		=> $row['cat_order'],
				'cat_name'		=> $row['cat_name'],
				'cat_nb'		=> $row['cat_nb'],
			);
			$i++;
			$cat_order = $row['cat_order'];
		}
		$this->db->sql_freeresult($result);

		if ($i > 0)
		{
			// Add the Unclassified category
			$list_cat[$i] = array(
				'cat_id'		=> $cat_id,
				'cat_order'		=> $cat_order + 1,
				'cat_name'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'cat_nb'		=> $this->smilies_count($cat_id),
			);

			$event['content'] = array_merge($event['content'], array(
				'title_cat'		=> $this->language->lang('ACP_SC_SMILIES'),
				'categories'	=> $list_cat,
			));
		}
	}

	public function shoutbox_smilies_popup($event)
	{
		$cat = $event['cat'];
		if ($cat > -1)
		{
			$i = 0;
			$title = $url = '';
			$smilies = array();
			$lang = $this->user->lang_name;

			if ($cat > 0)
			{
				$sql = 'SELECT cat_name
					FROM ' . $this->category_table . "
						WHERE cat_lang = '$lang'
						AND cat_id = $cat";
				$result = $this->db->sql_query_limit($sql, 1);
				$row = $this->db->sql_fetchrow($result);
				$cat_name = $row['cat_name'];
				$this->db->sql_freeresult($result);
			}
			else
			{
				$cat_name = $this->language->lang('SC_CATEGORY_DEFAUT');
			}

			$sql = $this->db->sql_build_query('SELECT', array(
				'SELECT'	=> '*',
				'FROM'		=> array(SMILIES_TABLE => ''),
				'WHERE'		=> "category = $cat",
				'ORDER_BY'	=> 'smiley_order ASC',
			));
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				if ($url === $row['smiley_url'])
				{
					continue;
				}
				$smilies[$i] = array(
					'nb'		=> $i,
					'code'		=> $row['code'],
					'emotion'	=> $row['emotion'],
					'width'		=> $row['smiley_width'],
					'height'	=> $row['smiley_height'],
					'image'		=> $row['smiley_url'],
				);
				$i++;
				$url = $row['smiley_url'];
			}

			$empty_row = ($i == 0) ? $this->language->lang('SC_SMILIES_EMPTY_CATEGORY') : false;

			$event['content'] = array_merge($event['content'], array(
				'in_cat'		=> true,
				'cat'			=> $cat,
				'total'			=> $i,
				'smilies'		=> $smilies,
				'emptyRow'		=> $empty_row,
				'title'			=> $this->language->lang('SC_CATEGORY_IN', $cat_name),
			));
		}
	}
}
