<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020-2023 Sylver35  https://breizhcode.com
* @license		http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace sylver35\smiliescat\controller;

use sylver35\smiliescat\core\category;
use phpbb\config\config;
use phpbb\db\driver\driver_interface as db;
use phpbb\pagination;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use phpbb\language\language;
use phpbb\log\log;

class admin_controller
{
	/* @var \sylver35\smiliescat\core\category */
	protected $category;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var string phpBB root path */
	protected $root_path;

	/** @var string Custom form action */
	protected $u_action;

	/**
	 * The database tables
	 *
	 * @var string */
	protected $smilies_category_table;

	/**
	 * Constructor
	 */
	public function __construct(category $category, config $config, db $db, pagination $pagination, request $request, template $template, user $user, language $language, log $log, $root_path, $smilies_category_table)
	{
		$this->category = $category;
		$this->config = $config;
		$this->db = $db;
		$this->pagination = $pagination;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->language = $language;
		$this->log = $log;
		$this->root_path = $root_path;
		$this->smilies_category_table = $smilies_category_table;
	}

	public function acp_smilies_category($id, $action)
	{
		$this->language->add_lang('acp/posting');
		$start = (int) $this->request->variable('start', 0);
		$select = (int) $this->request->variable('select', -1);
		$cat_id = (int) $this->request->variable('cat_id', 0);
		$ex_cat = (int) $this->request->variable('ex_cat', 0);
		$list = $this->request->variable('list', [0]);
		$form_key = 'sylver35/smiliescat';
		add_form_key($form_key);

		if ($action)
		{
			switch ($action)
			{
				case 'edit':
					$this->edit_smiley($id, $start);
				break;

				case 'edit_multi':
					$list = $this->request->variable('mark', [0]);
					$this->edit_multi_smiley($list, $start);
				break;

				case 'modify':
					if (!check_form_key($form_key))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

					$this->modify_smiley($id, $cat_id, $ex_cat);
					trigger_error($this->language->lang('SMILIES_EDITED', 1) . adm_back_link($this->u_action . '&amp;start=' . $start . '#acp_smilies_category'));
				break;

				case 'modify_list':
					foreach ($list as $smiley)
					{
						$this->modify_smiley($smiley, $cat_id);
					}
					trigger_error($this->language->lang('SMILIES_EDITED', count($list)) . adm_back_link($this->u_action . '&amp;start=' . $start . '#acp_smilies_category'));
				break;
			}

			$this->template->assign_var('IN_ACTION', true);
		}
		else
		{
			$this->extract_list_smilies($select, $start);

			$this->template->assign_vars([
				'LIST_CATEGORY'		=> $this->category->select_categories($select, true, true),
				'U_SELECT_CAT'		=> $this->u_action . '&amp;select=' . $select,
				'U_BACK'			=> ($select) ? $this->u_action : '',
			]);
		}

		$this->template->assign_vars([
			'CATEGORIE_SMILIES'	=> true,
		]);
	}

	public function acp_categories_config($id, $action, $mode)
	{
		$this->language->add_lang('acp/language');
		$form_key = 'sylver35/smiliescat';
		add_form_key($form_key);

		if ($action)
		{
			if (in_array($action, ['config_cat', 'add_cat', 'edit_cat']) && !check_form_key($form_key))
			{
				trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}

			switch ($action)
			{
				case 'config_cat':
					$this->config->set('smilies_per_page_cat', (int) $this->request->variable('smilies_per_page_cat', 15));

					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_CONFIG', time());
					trigger_error($this->language->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));
				break;

				case 'add':
					$this->add_cat();
				break;

				case 'add_cat':
					$this->add_category();
				break;

				case 'edit':
					$this->edit_cat((int) $id);
				break;

				case 'edit_cat':
					$this->edit_category((int) $id);
				break;

				case 'delete':
					if (confirm_box(true))
					{
						$this->delete_cat((int) $id);
					}
					else
					{
						confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields([
							'mode'		=> $mode,
							'id'		=> $id,
							'action'	=> $action,
						]));
					}
				break;
			}

			$this->template->assign_vars([
				'IN_ACTION'	=> true,
			]);
		}
		else
		{
			$this->category->adm_list_cat($this->u_action);
		}

		$this->template->assign_vars([
			'CATEGORIE_CONFIG'		=> true,
			'SMILIES_PER_PAGE_CAT'	=> $this->config['smilies_per_page_cat'],
			'U_ACTION_CONFIG'		=> $this->u_action . '&amp;action=config_cat',
			'U_ADD'					=> $this->u_action . '&amp;action=add',
		]);
	}

	private function modify_smiley($id, $cat_id, $ex_cat = -1)
	{
		if ($ex_cat == -1)
		{
			$sql = 'SELECT category
				FROM ' . SMILIES_TABLE . '
					WHERE smiley_id = ' . $id;
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
			$ex_cat = $row['category'];
		}
		
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

	private function extract_list_smilies($select, $start)
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
		$result = $this->db->sql_query_limit($sql, (int) $this->config['smilies_per_page_cat'], $start);
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
				'U_EDIT'		=> $this->u_action . '&amp;action=edit&amp;id=' . $row['smiley_id'] . '&amp;start=' . $start,
			]);
			$i++;

			// Keep this value in memory
			$cat = (int) $row['category'];
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars([
			'NB_SMILIES'	=> $this->language->lang('SC_SMILIES', ($smilies_count > 1) ? 2 : 1, $smilies_count),
			'U_SMILIES'		=> $this->root_path . $this->config['smilies_path'] . '/',
			'U_MODIFY'		=> $this->u_action . '&amp;action=edit_multi&ampstart=' . $start,
		]);

		$this->pagination->generate_template_pagination($this->u_action . '&amp;select=' . $select, 'pagination', 'start', $smilies_count, (int) $this->config['smilies_per_page_cat'], $start);
	}

	private function delete_cat($id)
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
		trigger_error($this->language->lang('SC_DELETE_SUCCESS') . adm_back_link($this->u_action));
	}

	private function add_category()
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
				trigger_error($this->language->lang('SC_CATEGORY_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
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
		trigger_error($this->language->lang('SC_CREATE_SUCCESS') . adm_back_link($this->u_action));
	}

	private function edit_category($id)
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
			$sort = (string) $this->request->variable("sort_$iso", '');
			$name = $this->category->capitalize($this->request->variable("name_$iso", '', true));

			if ($name === '')
			{
				trigger_error($this->language->lang('SC_CATEGORY_ERROR') . adm_back_link($this->u_action . '&amp;action=edit&amp;id=' . $id), E_USER_WARNING);
			}
			else
			{
				if ($sort === 'edit')
				{
					$this->db->sql_query('UPDATE ' . $this->smilies_category_table . " SET cat_name = '" . $this->db->sql_escape($name) . "', cat_title = '" . $this->db->sql_escape($title) . "', cat_nb = $cat_nb WHERE cat_lang = '" . $this->db->sql_escape($lang) . "' AND cat_id = $id");
				}
				else if ($sort === 'create')
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
		}
		$this->db->sql_freeresult($result);

		$this->db->sql_multi_insert($this->smilies_category_table, $sql_in);

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_EDIT_CAT', time(), [$title]);
		trigger_error($this->language->lang('SC_EDIT_SUCCESS') . adm_back_link($this->u_action));
	}

	private function add_cat()
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
			'U_BACK'			=> $this->u_action,
			'U_ADD_CAT'			=> $this->u_action . '&amp;action=add_cat',
		]);
	}

	private function edit_cat($id)
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
			'U_BACK'		=> $this->u_action,
			'U_EDIT_CAT'	=> $this->u_action . '&amp;action=edit_cat&amp;id=' . $id,
		]);
	}

	private function edit_smiley($id, $start)
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
			'SELECT_CATEGORY'	=> $this->category->select_categories($row['cat_id'], false, false),
			'IMG_SRC'			=> $this->root_path . $this->config['smilies_path'] . '/' . $row['smiley_url'],
			'U_MODIFY'			=> $this->u_action . '&amp;action=modify&amp;id=' . $row['smiley_id'] . '&amp;start=' . $start,
			'U_BACK'			=> $this->u_action,
			'S_IN_LIST'			=> false,
		]);
		$this->db->sql_freeresult($result);
	}

	private function edit_multi_smiley($list, $start)
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
			'SELECT_CATEGORY'	=> $this->category->select_categories(-1),
			'U_MODIFY'			=> $this->u_action . '&amp;action=modify_list&amp;start=' . $start,
			'S_IN_LIST'			=> true,
		]);
	}

	/**
	 * Set page url
	 *
	 * @param string $u_action Custom form action
	 * @return null
	 * @access public
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
