<?php
/**
 *
 * @package		Breizh Smilies Categories Extension
 * @copyright	(c) 2020-2023 Sylver35  https://breizhcode.com
 * @license		https://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

namespace sylver35\smiliescat\core;

use sylver35\smiliescat\core\category;
use phpbb\db\driver\driver_interface as db;
use phpbb\config\config;
use phpbb\user;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\pagination;

class smiley
{
	/* @var \sylver35\smiliescat\core\category */
	protected $category;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\pagination */
	protected $pagination;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/** @var string phpBB root path */
	protected $root_path;

	/**
	 * Constructor
	 */
	public function __construct(category $category, db $db, config $config, user $user, language $language, template $template, pagination $pagination, $smilies_category_table, $root_path)
	{
		$this->category = $category;
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;
		$this->language = $language;
		$this->template = $template;
		$this->pagination = $pagination;
		$this->smilies_category_table = $smilies_category_table;
		$this->root_path = $root_path;
	}

	public function smilies_count($cat, $compact = false)
	{
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> (!$compact) ? 'COUNT(DISTINCT smiley_id) AS smilies_count' : 'COUNT(DISTINCT smiley_url) AS smilies_count',
			'FROM'		=> [SMILIES_TABLE => ''],
			'WHERE'		=> ($cat > -1) ? 'category = ' . (int) $cat : "code <> ''",
		]);
		$result = $this->db->sql_query($sql);
		$nb = (int) $this->db->sql_fetchfield('smilies_count');
		$this->db->sql_freeresult($result);

		return $nb;
	}

	public function get_max_order()
	{
		// Get max order id...
		$sql = 'SELECT MAX(cat_order) AS maxi
			FROM ' . $this->smilies_category_table;
		$result = $this->db->sql_query($sql);
		$max = (int) $this->db->sql_fetchfield('maxi');
		$this->db->sql_freeresult($result);

		return $max;
	}

	public function modify_smiley($id, $cat_id, $ex_cat = -1)
	{
		$ex_cat = ($ex_cat == -1) ? $this->get_cat_id($id) : $ex_cat;

		$this->db->sql_query('UPDATE ' . SMILIES_TABLE . ' SET category = ' . $cat_id . ' WHERE smiley_id = ' . $id);
		$this->update_cat_smiley($cat_id, $ex_cat);
	}

	private function update_cat_smiley($cat_id, $ex_cat)
	{
		// Increment nb value if wanted
		if ($cat_id)
		{
			if ($this->category->get_first_order() === $cat_id)
			{
				if ($this->category->get_cat_nb($cat_id) === 0)
				{
					$this->config->set('smilies_category_nb', $cat_id);
				}
			}
			$this->db->sql_query('UPDATE ' . $this->smilies_category_table . ' SET cat_nb = cat_nb + 1 WHERE cat_id = ' . $cat_id);
		}

		// Decrement nb value if wanted
		if ($ex_cat)
		{
			$this->db->sql_query('UPDATE ' . $this->smilies_category_table . ' SET cat_nb = cat_nb - 1 WHERE cat_id = ' . $ex_cat);
		}
	}

	private function get_cat_id($id)
	{
		$sql = 'SELECT category
			FROM ' . SMILIES_TABLE . '
				WHERE smiley_id = ' . $id;
		$result = $this->db->sql_query($sql);
		$cat = (int) $this->db->sql_fetchfield('category');
		$this->db->sql_freeresult($result);

		return $cat;
	}

	public function extract_list_smilies($select, $start, $u_action)
	{
		$i = 0;
		$cat = -1;
		$lang = $this->user->lang_name;
		$smilies_count = (int) $this->category->smilies_count($select);

		if ($select === 0)
		{
			$sql = 'SELECT *
				FROM ' . SMILIES_TABLE . '
					WHERE category = 0
				ORDER BY smiley_order ASC';
		}
		else
		{
			$sql = $this->db->sql_build_query('SELECT', [
				'SELECT'	=> 's.*, c.*',
				'FROM'		=> [SMILIES_TABLE => 's'],
				'LEFT_JOIN'	=> [
					[
						'FROM'	=> [$this->smilies_category_table => 'c'],
						'ON'	=> "cat_id = category AND cat_lang = '$lang'",
					],
				],
				'WHERE'		=> ($select === -1) ? "code <> ''" : "cat_id = $select AND code <> ''",
				'ORDER_BY'	=> 'cat_order ASC, smiley_order ASC',
			]);
		}
		$result = $this->db->sql_query_limit($sql, (int) $this->config['smilies_per_page_acp'], $start);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$row['cat_name'] = ($row['category']) ? $row['cat_name'] : $this->language->lang('SC_CATEGORY_DEFAUT');
			$this->template->assign_block_vars('items', [
				'SPACER_CAT'	=> ($cat !== (int) $row['category']) ? $this->language->lang('SC_CATEGORY_IN', $row['cat_name']) : '',
				'IMG_SRC'		=> $row['smiley_url'],
				'WIDTH'			=> $row['smiley_width'],
				'HEIGHT'		=> $row['smiley_height'],
				'ID'			=> $row['smiley_id'],
				'CODE'			=> $row['code'],
				'EMOTION'		=> $row['emotion'],
				'CATEGORY'		=> $row['cat_name'],
				'U_EDIT'		=> $u_action . '&amp;action=edit&amp;id=' . $row['smiley_id'] . '&amp;start=' . $start,
			]);
			$i++;

			// Keep this value in memory
			$cat = (int) $row['category'];
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'NB_SMILIES'	=> $this->language->lang('SC_SMILIES', ($smilies_count > 1) ? 2 : 1, $smilies_count),
			'U_SMILIES'		=> $this->root_path . $this->config['smilies_path'] . '/',
		]);

		$this->pagination->generate_template_pagination($u_action . '&amp;select=' . $select, 'pagination', 'start', $smilies_count, (int) $this->config['smilies_per_page_cat'], $start);
	}

	public function edit_smiley($id, $start, $u_action)
	{
		$lang = $this->user->lang_name;
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> 's.*, c.*',
			'FROM'		=> [SMILIES_TABLE => 's'],
			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [$this->smilies_category_table => 'c'],
					'ON'	=> "c.cat_id = s.category AND c.cat_lang = '$lang'",
				],
			],
			'WHERE'	=> 's.smiley_id = ' . (int) $id,
		]);
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);

		$this->template->assign_vars([
			'WIDTH'				=> $row['smiley_width'],
			'HEIGHT'			=> $row['smiley_height'],
			'CODE'				=> $row['code'],
			'EMOTION'			=> $row['emotion'],
			'CATEGORY'			=> $row['cat_name'],
			'EX_CAT'			=> $row['cat_id'],
			'SELECT_CATEGORY'	=> $this->select_categories($row['cat_id'], false, false),
			'IMG_SRC'			=> $this->root_path . $this->config['smilies_path'] . '/' . $row['smiley_url'],
			'U_MODIFY'			=> $u_action . '&amp;action=modify&amp;id=' . $row['smiley_id'] . '&amp;start=' . $start,
			'U_BACK'			=> $u_action,
			'S_IN_LIST'			=> false,
		]);
		$this->db->sql_freeresult($result);
	}

	public function edit_multi_smiley($list, $start, $u_action)
	{
		$i = 0;
		$lang = $this->user->lang_name;
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> 's.*, c.*',
			'FROM'		=> [SMILIES_TABLE => 's'],
			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [$this->smilies_category_table => 'c'],
					'ON'	=> "c.cat_id = s.category AND cat_lang = '$lang'",
				],
			],
			'WHERE'		=> $this->db->sql_in_set('smiley_id', $list),
			'ORDER_BY'	=> 'cat_order ASC, s.smiley_order ASC',
		]);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$row['cat_name'] = ($row['category']) ? $row['cat_name'] : $this->language->lang('SC_CATEGORY_DEFAUT');
			$this->template->assign_block_vars('items', [
				'IMG_SRC'		=> $this->root_path . $this->config['smilies_path'] . '/' . $row['smiley_url'],
				'WIDTH'			=> $row['smiley_width'],
				'HEIGHT'		=> $row['smiley_height'],
				'ID'			=> $row['smiley_id'],
				'CODE'			=> $row['code'],
				'EMOTION'		=> $row['emotion'],
				'CATEGORY'		=> $row['cat_name'],
			]);
			$i++;
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'SELECT_CATEGORY'	=> $this->select_categories(-1),
			'U_MODIFY'			=> $u_action . '&amp;action=modify_list&amp;start=' . $start,
			'S_IN_LIST'			=> true,
		]);
	}

	public function select_categories($cat, $modify = false, $empty = false)
	{
		$lang = $this->user->lang_name;
		$select = '<option disabled="disabled">' . $this->language->lang('SC_CATEGORY_SELECT') . '</option>';
		if ($modify)
		{
			$selected = ((int) $cat === -1) ? ' selected="selected"' : '';
			$select .= '<option value="-1"' . $selected . '>' . $this->language->lang('SC_CATEGORY_ANY') . '</option>';
		}

		$sql = 'SELECT *
			FROM ' . $this->smilies_category_table . "
				WHERE cat_lang = '$lang'
				ORDER BY cat_order ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$row['cat_nb'] && $empty)
			{
				continue;
			}
			$selected = ((int) $cat === (int) $row['cat_id']) ? ' selected="selected"' : '';
			$select .= '<option title="' . $row['cat_name'] . '" value="' . $row['cat_id'] . '"' . $selected . '> ' . $row['cat_name'] . '</option>';
		}
		$this->db->sql_freeresult($result);

		$selected_default = (!$cat) ? ' selected="selected"' : '';
		$select .= '<option title="' . $this->language->lang('SC_CATEGORY_DEFAUT') . '" value="0"' . $selected_default . '> ' . $this->language->lang('SC_CATEGORY_DEFAUT') . '</option>';

		return $select;
	}
}
