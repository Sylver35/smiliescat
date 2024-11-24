<?php
/**
 *
 * @package		Breizh Smilies Categories Extension
 * @copyright	(c) 2020-2024 Sylver35  https://breizhcode.com
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
use phpbb\log\log;
use phpbb\request\request;
use phpbb\controller\helper;

class work
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

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\request\request */
	protected $request;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(category $category, db $db, config $config, user $user, language $language, template $template, log $log, request $request, helper $helper, $smilies_category_table)
	{
		$this->category = $category;
		$this->db = $db;
		$this->config = $config;
		$this->user = $user;
		$this->language = $language;
		$this->template = $template;
		$this->log = $log;
		$this->request = $request;
		$this->helper = $helper;
		$this->smilies_category_table = $smilies_category_table;
	}

	public function delete_cat($id, $u_action)
	{
		$sql = 'SELECT cat_title, cat_order
			FROM ' . $this->smilies_category_table . '
				WHERE cat_id = ' . $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$title = $row['cat_title'];
		$order = (int) $row['cat_order'];
		$this->db->sql_freeresult($result);

		$this->db->sql_query('DELETE FROM ' . $this->smilies_category_table . ' WHERE cat_id = ' . $id);

		// Decrement orders if needed
		$sql_decrement = 'SELECT cat_id, cat_order
			FROM ' . $this->smilies_category_table . '
				WHERE cat_order > ' . $order;
		$result = $this->db->sql_query($sql_decrement);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$new_order = (int) $row['cat_order'] - 1;
			$this->db->sql_query('UPDATE ' . $this->smilies_category_table . ' SET cat_order = ' . $new_order . ' WHERE cat_id = ' . $row['cat_id'] . ' AND cat_order = ' . $row['cat_order']);
		}
		$this->db->sql_freeresult($result);

		// Reset appropriate smilies category id
		$this->db->sql_query('UPDATE ' . SMILIES_TABLE . ' SET category = 0 WHERE category = ' . $id);

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_DELETE_CAT', time(), [$title]);
		trigger_error($this->language->lang('SC_DELETE_SUCCESS') . adm_back_link($u_action));
	}

	public function add_category($u_action)
	{
		$sql_ary = [];
		$title = (string) $this->request->variable('name_' . $this->user->lang_name, '', true);
		$cat_order = (int) $this->request->variable('order', 0);
		$cat_id = (int) $this->category->get_max_id() + 1;

		$sql = 'SELECT lang_id, lang_iso
			FROM ' . LANG_TABLE . "
				ORDER BY lang_id ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$iso = strtolower($row['lang_iso']);
			$lang = (string) $this->request->variable("lang_$iso", '', true);
			$name = (string) $this->request->variable("name_$iso", '', true);
			if ($name === '')
			{
				trigger_error($this->language->lang('SC_CATEGORY_ERROR') . adm_back_link($u_action . '&amp;action=add'), E_USER_WARNING);
			}
			else
			{
				$sql_ary[] = [
					'cat_id'		=> $cat_id,
					'cat_order'		=> $cat_order,
					'cat_lang'		=> $lang,
					'cat_name'		=> $this->category->capitalize($name),
					'cat_title'		=> $this->category->capitalize($title),
					'cat_nb'		=> 0,
				];
			}
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_multi_insert($this->smilies_category_table, $sql_ary);

		if ($cat_order === 1)
		{
			$sql = 'SELECT cat_id, cat_nb
				FROM ' . $this->smilies_category_table . '
					WHERE cat_order = 1';
			$result = $this->db->sql_query_limit($sql, 1);
			$row = $this->db->sql_fetchrow($result);
			if ($row['cat_nb'] > 0)
			{
				$this->config->set('smilies_category_nb', $row['cat_id']);
			}
			$this->db->sql_freeresult($result);
		}

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_ADD_CAT', time(), [$title]);
		trigger_error($this->language->lang('SC_CREATE_SUCCESS') . adm_back_link($u_action));
	}

	public function edit_category($id, $u_action)
	{
		$sql_in = [];
		$title = $this->category->capitalize($this->request->variable('name_' . $this->user->lang_name, '', true));
		$order = (int) $this->request->variable('order', 0);
		$cat_nb = (int) $this->request->variable('cat_nb', 0);

		$sql = 'SELECT lang_id, lang_iso
			FROM ' . LANG_TABLE . "
				ORDER BY lang_id ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$iso = strtolower($row['lang_iso']);
			$lang = (string) $this->request->variable("lang_$iso", '', true);
			$action = (string) $this->request->variable("sort_$iso", '');
			$name = $this->category->capitalize($this->request->variable("name_$iso", '', true));

			if ($name === '')
			{
				trigger_error($this->language->lang('SC_CATEGORY_ERROR') . adm_back_link($u_action . '&amp;action=edit&amp;id=' . $id), E_USER_WARNING);
			}
			else if ($action === 'edit')
			{
				$this->db->sql_query('UPDATE ' . $this->smilies_category_table . " SET cat_name = '" . $this->db->sql_escape($name) . "', cat_title = '" . $this->db->sql_escape($title) . "', cat_nb = $cat_nb WHERE cat_lang = '" . $this->db->sql_escape($lang) . "' AND cat_id = $id");
			}
			else if ($action === 'create')
			{
				$sql_in[] = [
					'cat_id'		=> $id,
					'cat_order'		=> $order,
					'cat_lang'		=> $lang,
					'cat_name'		=> $name,
					'cat_title'		=> $title,
					'cat_nb'		=> $cat_nb,
				];
			}
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_multi_insert($this->smilies_category_table, $sql_in);

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_EDIT_CAT', time(), [$title]);
		trigger_error($this->language->lang('SC_EDIT_SUCCESS') . adm_back_link($u_action));
	}

	public function add_cat($u_action)
	{
		$max = (int) $this->category->get_max_order() + 1;
		$sql = 'SELECT lang_local_name, lang_iso
			FROM ' . LANG_TABLE . '
				ORDER BY lang_id ASC';
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('categories', [
				'CAT_LANG'		=> $row['lang_local_name'],
				'CAT_ISO'		=> $row['lang_iso'],
				'CAT_ORDER'		=> $max,
			]);
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'IN_CAT_ACTION'		=> true,
			'IN_ADD_ACTION'		=> true,
			'CAT_ORDER'			=> $max,
			'U_BACK'			=> $u_action,
			'U_ADD_CAT'			=> $u_action . '&amp;action=add_cat',
		]);
	}

	public function edit_cat($id, $u_action)
	{
		// Get total lang id...
		$sql = 'SELECT COUNT(lang_id) as total
			FROM ' . LANG_TABLE;
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$title = '';
		$list_id = [];
		$i = $cat_order = $cat_nb = 0;
		$sql = $this->db->sql_build_query('SELECT', [
			'SELECT'	=> 'l.*, c.*',
			'FROM'		=> [LANG_TABLE => 'l'],
			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [$this->smilies_category_table => 'c'],
					'ON'	=> 'c.cat_lang = l.lang_iso',
				],
			],
			'WHERE'		=> 'cat_id = ' . $id,
			'ORDER_BY'	=> 'lang_id ASC',
		]);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->template->assign_block_vars('category_lang', [
				'CAT_LANG'			=> $row['lang_local_name'],
				'CAT_ISO'			=> $row['lang_iso'],
				'CAT_ORDER'			=> $row['cat_order'],
				'CAT_ID'			=> $row['cat_id'],
				'CAT_TRANSLATE'		=> $row['cat_name'],
				'CAT_SORT'			=> 'edit',
			]);
			$i++;
			$list_id[$i] = $row['lang_id'];
			$cat_order = $row['cat_order'];
			$title = $row['cat_title'];
			$cat_nb = $row['cat_nb'];
		}
		$this->db->sql_freeresult($result);

		// Add rows for empty langs in this category
		if ($i !== $total)
		{
			$sql = $this->db->sql_build_query('SELECT', [
				'SELECT'	=> '*',
				'FROM'		=> [LANG_TABLE => 'l'],
				'WHERE'		=> $this->db->sql_in_set('lang_id', $list_id, true, true),
			]);
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$this->template->assign_block_vars('category_lang', [
					'CAT_LANG'			=> $row['lang_local_name'],
					'CAT_ISO'			=> $row['lang_iso'],
					'CAT_ORDER'			=> $cat_order,
					'CAT_ID'			=> $id,
					'CAT_TRANSLATE'		=> '',
					'CAT_SORT'			=> 'create',
				]);
			}
			$this->db->sql_freeresult($result);
		}

		$this->template->assign_vars([
			'IN_CAT_ACTION'	=> true,
			'CAT_ORDER'		=> $cat_order,
			'CAT_NB'		=> $cat_nb,
			'CAT_TITLE'		=> $title,
			'U_BACK'		=> $u_action,
			'U_EDIT_CAT'	=> $u_action . '&amp;action=edit_cat&amp;id=' . $id,
		]);
	}

	public function adm_list_cat($u_action)
	{
		$i = 0;
		$cat = 0;
		$total = $this->category->category_exist();
		if ($total === 0)
		{
			$this->template->assign_vars([
				'EMPTY_ROW' =>	true,
			]);
		}
		else
		{
			$lang_cat = [];
			$langs = $this->category->get_langs();
			$max = $this->category->get_max_order();
			$sql = $this->db->sql_build_query('SELECT', [
				'SELECT'	=> 'l.lang_id, l.lang_iso, l.lang_local_name, c.*',
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
				$title = '';
				if ((int) $row['cat_id'] !== $cat)
				{
					$title = $this->language->lang('SC_CATEGORY_IN', $this->category->cat_name($row['cat_id']));
					$this->category->verify_cat_langs($langs, $cat, $i, $lang_cat, false);
				}
				$lang_cat[$row['cat_id']][$row['lang_id']] = $row['lang_iso'];
				$this->template->assign_block_vars('categories', [
					'CAT_ORDER'			=> (int) $row['cat_order'],
					'CAT_ID'			=> $row['cat_id'],
					'CAT_LANG'			=> $row['lang_local_name'],
					'CAT_ISO'			=> $row['lang_iso'],
					'CAT_TRANSLATE'		=> $row['cat_name'],
					'CAT_NB'			=> $row['cat_nb'],
					'ROW_MAX'			=> (int) $row['cat_order'] === $max,
					'TITLE_CAT'			=> $title,
					'U_EDIT'			=> $u_action . '&amp;action=edit&amp;id=' . $row['cat_id'],
					'U_DELETE'			=> $u_action . '&amp;action=delete&amp;id=' . $row['cat_id'],
				]);
				$i++;
				// Keep this value in memory
				$cat = (int) $row['cat_id'];

				if ((int) $row['cat_order'] === $max && ($i === $total))
				{
					$this->category->verify_cat_langs($langs, $cat, $i, $lang_cat, false);
				}
				
			}
			$this->db->sql_freeresult($result);
		}

		$this->template->assign_vars([
			'IN_LIST_CAT'	=> true,
			'U_ACTION'		=> $u_action,
			'U_MOVE_CATS'	=> $this->helper->route('sylver35_smiliescat_ajax_list_cat'),
		]);
	}
}
