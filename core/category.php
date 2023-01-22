<?php
/**
 *
 * @package		Breizh Smilies Categories Extension
 * @copyright	(c) 2020-2023 Sylver35  https://breizhcode.com
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

namespace sylver35\smiliescat\core;

use phpbb\cache\driver\driver_interface as cache;
use phpbb\db\driver\driver_interface as db;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\user;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\extension\manager;
use phpbb\log\log;

class category
{
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\extension\manager "Extension Manager" */
	protected $ext_manager;

	/** @var \phpbb\log\log */
	protected $log;

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
	public function __construct(cache $cache, db $db, config $config, helper $helper, user $user, language $language, template $template, manager $ext_manager, log $log, $smilies_category_table, $root_path)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->config = $config;
		$this->helper = $helper;
		$this->user = $user;
		$this->language = $language;
		$this->template = $template;
		$this->ext_manager = $ext_manager;
		$this->log = $log;
		$this->smilies_category_table = $smilies_category_table;
		$this->root_path = $root_path;
	}

	public function get_version()
	{
		if (($data = $this->cache->get('_smiliescat_version')) === false)
		{
			$md_manager = $this->ext_manager->create_extension_metadata_manager('sylver35/smiliescat');
			$meta = $md_manager->get_metadata();

			$data = [
				'version'	=> $meta['version'],
				'homepage'	=> $meta['homepage'],
			];
			// cache for 7 days
			$this->cache->put('_smiliescat_version', $data, 604800);
		}

		return $data;
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

	public function capitalize($var)
	{
		return ucfirst(strtolower(trim($var)));
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

	public function get_max_id()
	{
		// Get max id...
		$sql = 'SELECT MAX(cat_id) AS id_max
			FROM ' . $this->smilies_category_table;
		$result = $this->db->sql_query($sql);
		$id_max = (int) $this->db->sql_fetchfield('id_max');
		$this->db->sql_freeresult($result);

		return $id_max;
	}

	public function get_first_order()
	{
		// Get first order id...
		$sql = 'SELECT cat_order, cat_id
			FROM ' . $this->smilies_category_table . '
			ORDER BY cat_order ASC';
		$result = $this->db->sql_query_limit($sql, 1);
		$first = (int) $this->db->sql_fetchfield('cat_id');
		$this->db->sql_freeresult($result);

		return $first;
	}

	public function get_cat_nb($id)
	{
		$sql = 'SELECT cat_nb
			FROM ' . $this->smilies_category_table . '
				WHERE cat_id = ' . (int) $id;
		$result = $this->db->sql_query($sql);
		$cat_nb = (int) $this->db->sql_fetchfield('cat_nb');
		$this->db->sql_freeresult($result);

		return $cat_nb;
	}

	public function extract_list_categories($cat)
	{
		$title = '';
		$cat_order = $i = 0;
		$lang = $this->user->lang_name;
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> '*',
			'FROM'		=> [$this->smilies_category_table => ''],
			'WHERE'		=> "cat_nb <> 0 AND cat_lang = '$lang'",
			'ORDER_BY'	=> 'cat_order ASC',
		]);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$actual_cat = (int) $row['cat_id'] === $cat;
			$this->template->assign_block_vars('categories', [
				'CLASS'			=> $actual_cat ? 'cat-active' : 'cat-inactive',
				'SEPARATE'		=> ($i > 0) ? ' - ' : '',
				'CAT_NAME'		=> $row['cat_name'] ? $row['cat_name'] : $row['cat_title'],
				'CAT_ORDER'		=> $row['cat_order'],
				'CAT_ID'		=> $row['cat_id'],
				'CAT_NB'		=> $row['cat_nb'],
				'U_CAT'			=> $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => $row['cat_id']]),
			]);
			$i++;

			// Keep these values in memory
			$title = $actual_cat ? $row['cat_name'] : $title;
			$cat_order = $row['cat_order'];
		}
		$this->db->sql_freeresult($result);

		// Add the Unclassified category if not empty
		if ($nb = $this->smilies_count(0))
		{
			$this->template->assign_block_vars('categories', [
				'CLASS'			=> ($cat === 0) ? 'cat-active' : 'cat-inactive',
				'SEPARATE'		=> ($i > 0) ? ' - ' : '',
				'CAT_NAME'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'CAT_ORDER'		=> $cat_order + 1,
				'CAT_ID'		=> 0,
				'CAT_NB'		=> $nb,
				'U_CAT'			=> $this->helper->route('sylver35_smiliescat_smilies_pop', ['select' => 0]),
			]);
		}

		return (!$cat) ? $this->language->lang('SC_CATEGORY_DEFAUT') : $title;
	}

	public function shoutbox_smilies($event)
	{
		$i = $cat_order = 0;
		$list_cat = [];
		$lang = $this->user->lang_name;

		$sql = 'SELECT * 
			FROM ' . $this->smilies_category_table . "
				WHERE cat_lang = '$lang'
				ORDER BY cat_order ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			// Choose only non-empty categories
			if ($row['cat_nb'])
			{
				$list_cat[$i] = [
					'cat_id'		=> (int) $row['cat_id'],
					'cat_order'		=> (int) $row['cat_order'],
					'cat_name'		=> (string) $row['cat_name'],
					'cat_nb'		=> (int) $row['cat_nb'],
				];
				$i++;
			}
			// Keep this value in memory
			$cat_order = (int) $row['cat_order'];
		}
		$this->db->sql_freeresult($result);

		// Add the Unclassified category if not empty
		if ($nb = $this->smilies_count(0, true))
		{
			$list_cat[$i] = [
				'cat_id'		=> 0,
				'cat_order'		=> $cat_order + 1,
				'cat_name'		=> $this->language->lang('SC_CATEGORY_DEFAUT'),
				'cat_nb'		=> $nb,
			];
		}

		$event['content'] = array_merge($event['content'], [
			'title_cat'		=> $this->language->lang('ACP_SC_SMILIES'),
			'categories'	=> $list_cat,
		]);
	}

	public function shoutbox_smilies_popup($event)
	{
		$cat = (int) $event['cat'];
		if ($cat !== -1)
		{
			$i = 0;
			$smilies = [];
			$cat_name = $this->get_cat_name($cat);

			$sql = [
				'SELECT'	=> 'smiley_url, MIN(smiley_id) AS smiley_id, MIN(code) AS code, MIN(smiley_order) AS min_smiley_order, MIN(smiley_width) AS smiley_width, MIN(smiley_height) AS smiley_height, MIN(emotion) AS emotion',
				'FROM'		=> [SMILIES_TABLE => ''],
				'WHERE'		=> 'category = ' . $cat,
				'GROUP_BY'	=> 'smiley_url',
				'ORDER_BY'	=> 'min_smiley_order ASC',
			];
			$result = $this->db->sql_query($this->db->sql_build_query('SELECT', $sql));
			while ($row = $this->db->sql_fetchrow($result))
			{
				$smilies[$i] = [
					'nb'		=> (int) $i,
					'code'		=> (string) $row['code'],
					'emotion'	=> (string) $row['emotion'],
					'image'		=> (string) $row['smiley_url'],
					'width'		=> (int) $row['smiley_width'],
					'height'	=> (int) $row['smiley_height'],
				];
				$i++;
			}
			$this->db->sql_freeresult($result);

			$event['content'] = array_merge($event['content'], [
				'in_cat'		=> true,
				'cat'			=> $cat,
				'total'			=> $i,
				'smilies'		=> $smilies,
				'emptyRow'		=> ($i === 0) ? $this->language->lang('SC_SMILIES_EMPTY_CATEGORY') : '',
				'title'			=> $this->language->lang('SC_CATEGORY_IN', $cat_name),
			]);
		}
	}

	private function get_cat_name($cat)
	{
		if ($cat > 0)
		{
			$lang = $this->user->lang_name;
			$sql = 'SELECT cat_name
				FROM ' . $this->smilies_category_table . "
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

		return $cat_name;
	}

	public function set_order($action, $current_order)
	{
		$switch_order_id = 0;
		$max_order = $this->get_max_order();
		if ($current_order === 1 && $action === 'move_up')
		{
			return $switch_order_id;
		}

		if (($current_order === $max_order) && ($action === 'move_down'))
		{
			return $switch_order_id;
		}

		// on move_down, switch position with next order_id...
		// on move_up, switch position with previous order_id...
		$switch_order_id = ($action === 'move_down') ? $current_order + 1 : $current_order - 1;

		return $switch_order_id;
	}

	public function adm_list_cat($u_action)
	{
		$i = $cat = 0;
		$max = $this->get_max_order();
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> 'l.lang_iso, l.lang_local_name, c.*',
			'FROM'		=> [LANG_TABLE => 'l'],
			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [$this->smilies_category_table => 'c'],
					'ON'	=> 'cat_lang = lang_iso',
				],
			],
			'ORDER_BY'	=> 'cat_order ASC, cat_lang_id ASC',
		]);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if (!$row)
			{
				$this->template->assign_vars([
					'EMPTY_ROW' =>	true,
				]);
			}
			else
			{
				$this->template->assign_block_vars('categories', [
					'CAT_NR'			=> $i + 1,
					'LANG_EMPTY'		=> !$row['cat_id'] && !$row['cat_order'] && !$row['cat_name'],
					'SPACER_CAT'		=> $this->language->lang('SC_CATEGORY_IN', $row['cat_title']),
					'CAT_LANG'			=> $row['lang_local_name'],
					'CAT_ISO'			=> $row['lang_iso'],
					'CAT_ID'			=> $row['cat_id'],
					'CAT_ORDER'			=> $row['cat_order'],
					'CAT_TRANSLATE'		=> $row['cat_name'],
					'CAT_NB'			=> $row['cat_nb'],
					'ROW'				=> (int) $row['cat_id'] !== $cat,
					'ROW_MAX'			=> (int) $row['cat_order'] === $max,
					'U_EDIT'			=> $u_action . '&amp;action=edit&amp;id=' . $row['cat_id'],
					'U_DELETE'			=> $u_action . '&amp;action=delete&amp;id=' . $row['cat_id'],
				]);
				$i++;
				// Keep this value in memory
				$cat = (int) $row['cat_id'];
			}
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'IN_LIST_CAT'	=> true,
			'U_ACTION'		=> $u_action,
			'U_MOVE_CATS'	=> $this->helper->route('sylver35_smiliescat_ajax_list_cat'),
		]);
	}

	public function reset_first_cat($current_order, $switch_order_id)
	{
		$first = $this->get_first_order();
		if ($current_order === 1 || $switch_order_id === 1)
		{
			if ($this->get_cat_nb($first) > 0)
			{
				$this->config->set('smilies_category_nb', $first);
			}
		}
	}

	public function move_cat($id, $action)
	{
		// Get current order id and title...
		$sql = 'SELECT cat_order, cat_title
			FROM ' . $this->smilies_category_table . '
				WHERE cat_id = ' . $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$current_order = (int) $row['cat_order'];
		$title = $row['cat_title'];
		$this->db->sql_freeresult($result);

		$switch_order_id = $this->set_order($action, $current_order);
		if ($switch_order_id === 0)
		{
			return;
		}

		$this->db->sql_query('UPDATE ' . $this->smilies_category_table . " SET cat_order = $current_order WHERE cat_order = $switch_order_id AND cat_id <> $id");
		$move_executed = (bool) $this->db->sql_affectedrows();

		// Only update the other entry too if the previous entry got updated
		if ($move_executed)
		{
			$this->db->sql_query('UPDATE ' . $this->smilies_category_table . " SET cat_order = $switch_order_id WHERE cat_order = $current_order AND cat_id = $id");
		}

		$this->reset_first_cat($current_order, $switch_order_id);
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_' . strtoupper($action) . '_CAT', time(), [$title]);
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
				'IMG_SRC'		=>  $this->root_path . $this->config['smilies_path'] . '/' . $row['smiley_url'],
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
}
