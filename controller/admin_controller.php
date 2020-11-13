<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020 Sylver35  https://breizhcode.com
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

	public function acp_smilies_category()
	{
		$this->language->add_lang('acp/posting');
		$action = (string) $this->request->variable('action', '');
		$start = (int) $this->request->variable('start', 0);
		$select = (int) $this->request->variable('select', -1);
		$id = (int) $this->request->variable('id', -1);
		$form_key = 'sylver35/smiliescat';
		add_form_key($form_key);

		if ($action)
		{
			switch ($action)
			{
				case 'edit':
					$this->category->adm_edit_smiley($id, $this->u_action, $start);
				break;

				case 'modify':
					if (!check_form_key($form_key))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

					$this->modify_smiley($id, (int) $this->request->variable('cat_id', 0), (int) $this->request->variable('ex_cat', 0));
					trigger_error($this->language->lang('SMILIES_EDITED', 1) . adm_back_link($this->u_action . '&amp;start=' . $start . '#acp_smilies_category'));
				break;
			}

			$this->template->assign_var('IN_ACTION', true);
		}
		else
		{
			$this->extract_list_smilies($select, $start);

			$this->template->assign_vars([
				'LIST_CATEGORY'		=> $this->category->select_categories($select, true),
				'U_SELECT_CAT'		=> $this->u_action . '&amp;select=' . $select,
				'U_BACK'			=> ($select) ? $this->u_action : '',
			]);
		}

		$this->template->assign_var('CATEGORIE_SMILIES', true);
	}

	public function acp_categories_config()
	{
		$this->language->add_lang('acp/language');
		$mode = (string) $this->request->variable('mode', '');
		$action = (string) $this->request->variable('action', '');
		$id = (int) $this->request->variable('id', 0);
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
					$this->category->adm_add_cat($this->u_action);
				break;

				case 'add_cat':
					$this->add_category();
				break;

				case 'edit':
					$this->category->adm_edit_cat($id, $this->u_action);
				break;

				case 'edit_cat':
					$this->edit_category($id);
				break;

				case 'delete':
					if (confirm_box(true))
					{
						$this->delete_cat($id);
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

			$this->template->assign_var('IN_ACTION', true);
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

	private function modify_smiley($id, $cat_id, $ex_cat)
	{
		$sql = 'UPDATE ' . SMILIES_TABLE . ' SET category = ' . (int) $cat_id . ' WHERE smiley_id = ' . (int) $id;
		$this->db->sql_query($sql);

		// Decrement nb value if wanted
		if ($ex_cat)
		{
			$sql_decrement = 'UPDATE ' . $this->smilies_category_table . ' SET cat_nb = cat_nb - 1 WHERE cat_id = ' . (int) $ex_cat;
			$this->db->sql_query($sql_decrement);
		}

		// Increment nb value if wanted
		if ($cat_id)
		{
			$sql_increment = 'UPDATE ' . $this->smilies_category_table . ' SET cat_nb = cat_nb + 1 WHERE cat_id = ' . (int) $cat_id;
			$this->db->sql_query($sql_increment);
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
			$sql = $this->db->sql_build_query('SELECT', [
				'SELECT'	=> '*',
				'FROM'		=>[SMILIES_TABLE => ''],
				'WHERE'		=> 'category = 0',
				'ORDER_BY'	=> 'smiley_order ASC',
			]);
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
				'WHERE'		=> ($select == -1) ? "code <> ''" : "cat_id = $select AND code <> ''",
				'ORDER_BY'	=> 'cat_order ASC, smiley_order ASC',
			]);
		}
		$result = $this->db->sql_query_limit($sql, (int) $this->config['smilies_per_page_cat'], $start);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$row['category'] = isset($row['category']) ? $row['category'] : 0;
			$row['cat_name'] = ($row['category']) ? $row['cat_name'] : $this->language->lang('SC_CATEGORY_DEFAUT');

			$this->template->assign_block_vars('items', [
				'SPACER_CAT'	=> ($cat !== (int) $row['category']) ? $this->language->lang('SC_CATEGORY_IN', $row['cat_name']) : '',
				'IMG_SRC'		=> $row['smiley_url'],
				'WIDTH'			=> $row['smiley_width'],
				'HEIGHT'		=> $row['smiley_height'],
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
		]);

		$this->pagination->generate_template_pagination($this->u_action . '&amp;select=' . $select, 'pagination', 'start', $smilies_count, (int) $this->config['smilies_per_page_cat'], $start);
	}

	private function delete_cat($id)
	{
		$id = (int) $id;
		$sql = 'SELECT cat_title, cat_order
			FROM ' . $this->smilies_category_table . '
				WHERE cat_id = ' . $id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$title = $row['cat_title'];
		$order = $row['cat_order'];
		$this->db->sql_freeresult($result);

		$sql_delete = 'DELETE FROM ' . $this->smilies_category_table . ' WHERE cat_id = ' . $id;
		$this->db->sql_query($sql_delete);

		// Decrement orders if needed
		$sql_decrement = 'SELECT cat_id, cat_order
			FROM ' . $this->smilies_category_table . '
				WHERE cat_order > ' . (int) $order;
		$result = $this->db->sql_query($sql_decrement);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$new_order = (int) $row['cat_order'] - 1;
			$sql_order = 'UPDATE ' . $this->smilies_category_table . '
				SET cat_order = ' . $new_order . '
					WHERE cat_id = ' . $row['cat_id'] . ' AND cat_order = ' . $row['cat_order'];
			$this->db->sql_query($sql_order);
		}
		$this->db->sql_freeresult($result);

		// Reset appropriate smilies category id
		$sql_update = 'UPDATE ' . SMILIES_TABLE . ' SET category = 0 WHERE category = ' . $id;
		$this->db->sql_query($sql_update);

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_DELETE_CAT', time(), [$title]);
		trigger_error($this->language->lang('SC_DELETE_SUCCESS') . adm_back_link($this->u_action));
	}

	private function add_category()
	{
		$title = (string) $this->request->variable('name_' . $this->user->lang_name, '', true);
		$cat_order = (int) $this->request->variable('order', 0);
		$cat_id = (int) $this->category->get_max_id() + 1;
		$sql_in = [];
		$i = 0;

		$sql = 'SELECT lang_id, lang_iso
			FROM ' . LANG_TABLE . "
				ORDER BY lang_id ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$iso = $row['lang_iso'];
			$lang = (string) $this->request->variable("lang_$iso", '', true);
			$name = (string) $this->request->variable("name_$iso", '', true);
			if ($name === '')
			{
				trigger_error($this->language->lang('SC_CATEGORY_ERROR') . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
			}
			else
			{
				$sql_in[$i] = [
					'cat_id'		=> $cat_id,
					'cat_order'		=> $cat_order,
					'cat_lang'		=> $lang,
					'cat_name'		=> $this->category->capitalize($name),
					'cat_title'		=> $this->category->capitalize($title),
				];
			}
			$i++;
		}

		for ($j = 0; $j < $i; $j++)
		{
			$this->db->sql_query('INSERT INTO ' . $this->smilies_category_table . $this->db->sql_build_array('INSERT', $sql_in[$j]));
		}

		if ($cat_order === 1)
		{
			$this->config->set('smilies_category_nb', $cat_id);
		}

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_ADD_CAT', time(), [$title]);
		trigger_error($this->language->lang('SC_CREATE_SUCCESS') . adm_back_link($this->u_action));
	}

	private function edit_category($id)
	{
		$id = (int) $id;
		$title = $this->category->capitalize($this->request->variable('name_' . $this->user->lang_name, '', true));

		$sql = 'SELECT lang_id, lang_iso
			FROM ' . LANG_TABLE . "
				ORDER BY lang_id ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$iso = $row['lang_iso'];
			$lang = (string) $this->request->variable("lang_$iso", '', true);
			$sort = (string) $this->request->variable("sort_$iso", '');
			$order = (int) $this->request->variable('order', 0);
			$name = $this->category->capitalize($this->request->variable("name_$iso", '', true));
			if ($name === '')
			{
				trigger_error($this->language->lang('SC_CATEGORY_ERROR') . adm_back_link($this->u_action . '&amp;action=edit&amp;id=' . $id), E_USER_WARNING);
			}
			else
			{
				if ($sort === 'edit')
				{
					$sql = 'UPDATE ' . $this->smilies_category_table . "
						SET cat_name = '" . $name . "', cat_title = '" . $title . "'
							WHERE cat_lang = '" . $this->db->sql_escape($lang) . "'
							AND cat_id = $id";
					$this->db->sql_query($sql);
				}
				else if ($sort === 'create')
				{
					$sql_in = [
						'cat_id'		=> $id,
						'cat_order'		=> $order,
						'cat_lang'		=> $lang,
						'cat_name'		=> $name,
						'cat_title'		=> $title,
					];
					$this->db->sql_query('INSERT INTO ' . $this->smilies_category_table . $this->db->sql_build_array('INSERT', $sql_in));
				}
			}
		}

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_EDIT_CAT', time(), [$title]);
		trigger_error($this->language->lang('SC_EDIT_SUCCESS') . adm_back_link($this->u_action));
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
