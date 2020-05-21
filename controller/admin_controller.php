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

	public function acp_categories_config()
	{
		$this->language->add_lang('acp/language');
		$mode = $this->request->variable('mode', '');
		$action = $this->request->variable('action', '');
		$id = $this->request->variable('id', 0);
		$form_key = 'sylver35/smiliescat';
		add_form_key($form_key);

		if ($action)
		{
			switch ($action)
			{
				case 'config_cat':

					if (!check_form_key($form_key))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

					$this->config->set('smilies_per_page_cat', $this->request->variable('smilies_per_page_cat', 15));

					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_CONFIG', time());
					trigger_error($this->language->lang('CONFIG_UPDATED') . adm_back_link($this->u_action));

				break;

				case 'add':

					$this->category->adm_add_cat();

					$this->template->assign_vars(array(
						'IN_ADD_ACTION'			=> true,
						'U_BACK'				=> $this->u_action,
						'U_ADD_CAT'				=> $this->u_action . '&amp;action=add_cat',
					));

				break;

				case 'add_cat':

					if (!check_form_key($form_key))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

					$this->add_categorie();

				break;

				case 'edit':

					$this->category->adm_edit_cat($id);

					$this->template->assign_vars(array(
						'U_BACK'		=> $this->u_action,
						'U_EDIT_CAT'	=> $this->u_action . '&amp;action=edit_cat&amp;id=' . $id,
					));

				break;

				case 'edit_cat':

					if (!check_form_key($form_key))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

					$this->edit_categorie($id);

				break;

				case 'move_up':
				case 'move_down':

					if (!check_link_hash($this->request->variable('hash', ''), 'acp-main_module'))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}
					
					$result = $this->move_cat($action, $id);
					
					if ($result['return'] === false)
					{
						break;
					}

					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_' . strtoupper($action) . '_CAT', time(), array($result['title']));

					if ($this->request->is_ajax())
					{
						trigger_error($this->language->lang('SC_MOVE_SUCCESS') . adm_back_link($this->u_action));
						$json_response = new \phpbb\json_response;
						$json_response->send(array(
							'MESSAGE_TITLE'	=> $this->language->lang('INFORMATION'),
							'MESSAGE_TEXT'	=> $this->language->lang('SC_MOVE_SUCCESS'),
							'REFRESH_DATA'	=> array('time'		=> 2),
						));
					}
					else
					{
						trigger_error($this->language->lang('SC_MOVE_SUCCESS') . adm_back_link($this->u_action));
					}

				break;

				case 'delete':

					if (confirm_box(true))
					{
						$this->delete_cat($id);

						if ($this->request->is_ajax())
						{
							trigger_error($this->language->lang('SC_DELETE_SUCCESS') . adm_back_link($this->u_action));
							$json_response = new \phpbb\json_response;
							$json_response->send(array(
								'MESSAGE_TITLE'	=> $this->language->lang('INFORMATION'),
								'MESSAGE_TEXT'	=> $this->language->lang('SC_DELETE_SUCCESS'),
								'REFRESH_DATA'	=> array('time' => 2),
							));
						}
						else
						{
							trigger_error($this->language->lang('SC_DELETE_SUCCESS') . adm_back_link($this->u_action));
						}
					}
					else
					{
						confirm_box(false, $this->language->lang('CONFIRM_OPERATION'), build_hidden_fields(array(
							'mode'		=> $mode,
							'id'		=> $id,
							'action'	=> 'delete',
						)));
					}

				break;
				
			}

			$this->template->assign_vars(array(
				'IN_ACTION'		=> true,
			));
		}
		else
		{
			$this->category->adm_list_cat($this->u_action);
		}

		$this->template->assign_vars(array(
			'CATEGORIE_CONFIG'		=> true,
			'SMILIES_PER_PAGE_CAT'	=> $this->config['smilies_per_page_cat'],
			'U_ADD'					=> $this->u_action . '&amp;action=add',
		));
	}

	public function acp_smilies_category()
	{
		$this->language->add_lang('acp/posting');
		$action = $this->request->variable('action', '');
		$start = $this->request->variable('start', 0);
		$select = $this->request->variable('select', -1);
		$id = $this->request->variable('id', -1);
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

					$cat_id = $this->request->variable('cat_id', 0);
					$ex_cat = $this->request->variable('ex_cat', 0);

					$sql = 'UPDATE ' . SMILIES_TABLE . " SET category = $cat_id WHERE smiley_id = $id";
					$this->db->sql_query($sql);

					// Decrement nb value if wanted
					if ($ex_cat != 0)
					{
						$sql = 'UPDATE ' . $this->smilies_category_table . " SET cat_nb = cat_nb - 1 WHERE cat_id = $ex_cat";
						$this->db->sql_query($sql);
					}
					// Increment nb value if wanted
					if ($cat_id != 0)
					{
						$sql = 'UPDATE ' . $this->smilies_category_table . " SET cat_nb = cat_nb + 1 WHERE cat_id = $cat_id";
						$this->db->sql_query($sql);
					}

					trigger_error($this->language->lang('SMILIES_EDITED', 1) . adm_back_link($this->u_action . '&amp;start=' . $start . '#acp_smilies_category'));

				break;
			}

			$this->template->assign_vars(array(
				'IN_ACTION'			=> true,
			));
		}
		else
		{
			$this->extract_list_smilies($select, $start);

			$this->template->assign_vars(array(
				'LIST_CATEGORY'		=> $this->category->select_categories($select),
				'U_BACK'			=> ($select) ? $this->u_action : false,
				'U_SELECT_CAT'		=> $this->u_action . '&amp;select=' . $select,
			));
		}

		$this->template->assign_vars(array(
			'CATEGORIE_SMILIES'		=> true,
		));
	}
	
	private function move_cat($action, $id)
	{
		// Get current order id and title...
		$sql = 'SELECT cat_order, cat_title
			FROM ' . $this->smilies_category_table . "
				WHERE cat_id = $id";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$current_order = $row['cat_order'];
		$title = $row['cat_title'];
		$this->db->sql_freeresult($result);

		if ($current_order == 1 && $action == 'move_up')
		{
			return array(
				'return'	=> false,
				'title'		=> '',
			);
		}

		$max_order = $this->category->get_max_order();

		if ($current_order == $max_order && $action == 'move_down')
		{
			return array(
				'return'	=> false,
				'title'		=> '',
			);
		}

		// on move_down, switch position with next order_id...
		// on move_up, switch position with previous order_id...
		$switch_order_id = ($action == 'move_down') ? $current_order + 1 : $current_order - 1;

		$sql = 'UPDATE ' . $this->smilies_category_table . "
			SET cat_order = $current_order
			WHERE cat_order = $switch_order_id
				AND cat_id <> $id";
		$this->db->sql_query($sql);
		$move_executed = (bool) $this->db->sql_affectedrows();

		// Only update the other entry too if the previous entry got updated
		if ($move_executed)
		{
			$sql = 'UPDATE ' . $this->smilies_category_table . "
				SET cat_order = $switch_order_id
				WHERE cat_order = $current_order
					AND cat_id = $id";
			$this->db->sql_query($sql);
		}

		$this->category->reset_first_cat($current_order, $switch_order_id);

		return array(
			'return'	=> true,
			'title'		=> $title,
		);
	}
	
	private function extract_list_smilies($select, $start)
	{
		$cat = $i = 0;
		$smiley_url = '';
		$lang = $this->user->lang_name;
		$smilies_count = $this->category->smilies_count($select);
		$cat_title = $this->language->lang('SC_CATEGORY_DEFAUT');
		$where = ($select !== -1) ? "cat_id = $select AND " : '';

		if ($select !== 0)
		{
			$sql = $this->db->sql_build_query('SELECT', array(
				'SELECT'	=> 's.*, c.*',
				'FROM'		=> array(SMILIES_TABLE => 's'),
				'LEFT_JOIN'	=> array(
					array(
						'FROM'	=> array($this->smilies_category_table => 'c'),
						'ON'	=> "cat_id = category AND cat_lang = '$lang'",
					),
				),
				'WHERE'		=> "$where code <> ''",
				'ORDER_BY'	=> 'cat_order ASC, smiley_order ASC',
			));
		}
		else
		{
			$sql = $this->db->sql_build_query('SELECT', array(
				'SELECT'	=> '*',
				'FROM'		=> array(SMILIES_TABLE => ''),
				'WHERE'		=> "category = 0",
				'ORDER_BY'	=> 'smiley_order ASC',
			));
		}
		$result = $this->db->sql_query_limit($sql, (int) $this->config['smilies_per_page_cat'], $start);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($smiley_url == $row['smiley_url'])
			{
				continue;
			}
			$on_spacer = ($cat !== $row['category']) ? true : false;
			$title = ($row['category'] == 0) ? $this->language->lang('SC_SMILIES_NO_CATEGORY') : $this->language->lang('SC_CATEGORY_IN', $row['cat_name']);
			$this->template->assign_block_vars('items', array(
				'S_SPACER_CAT'	=> $on_spacer,
				'SPACER_CAT'	=> $title,
				'IMG_SRC'		=> $this->root_path . $this->config['smilies_path'] . '/' . $row['smiley_url'],
				'WIDTH'			=> $row['smiley_width'],
				'HEIGHT'		=> $row['smiley_height'],
				'CODE'			=> $row['code'],
				'EMOTION'		=> $row['emotion'],
				'CATEGORY'		=> (isset($row['cat_name'])) ? $row['cat_name'] : '',
				'U_EDIT'		=> $this->u_action . '&amp;action=edit&amp;id=' . $row['smiley_id'] . '&amp;start=' . $start,
			));
			$i++;
			$smiley_url = $row['smiley_url'];
			$cat = $row['category'];
			$cat_title = ($select > 0) ? $row['cat_name'] : $cat_title;
		}
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'NB_SMILIES'		=> $smilies_count,
			'S_SPACER_ANY'		=> ($cat == 0) ? true : false,
			'CAT_SELECT_TITLE'	=> ($select != -1) ? $this->language->lang('SC_CATEGORY_IN', $cat_title) : false,
		));

		$this->pagination->generate_template_pagination($this->u_action . '&amp;select=' . $select, 'pagination', 'start', $smilies_count, (int) $this->config['smilies_per_page_cat'], $start);
	}

	private function delete_cat($id)
	{
		$sql = 'SELECT cat_title, cat_order
			FROM ' . $this->smilies_category_table . "
				WHERE cat_id = $id";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$title = $row['cat_title'];
		$order = $row['cat_order'];
		$this->db->sql_freeresult($result);

		$sql_delete = 'DELETE FROM ' . $this->smilies_category_table . " WHERE cat_id = $id";
		$this->db->sql_query($sql_delete);

		// Decrement orders if needed
		$sql_decrement = 'SELECT cat_id, cat_order
			FROM ' . $this->smilies_category_table . "
				WHERE cat_order > $order";
		$result = $this->db->sql_query($sql_decrement);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$new_order = $row['cat_order'] - 1;
			$sql_order = 'UPDATE ' . $this->smilies_category_table . '
				SET cat_order = ' . $new_order . '
					WHERE cat_id = ' . $row['cat_id'] . ' AND cat_order = ' . $row['cat_order'];
			$this->db->sql_query($sql_order);
		}
		$this->db->sql_freeresult($result);

		// Reset appropriate smilies category id
		$sql_update = 'UPDATE ' . SMILIES_TABLE . " SET category = 0 WHERE category = $id";
		$this->db->sql_query($sql_update);

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_DELETE_CAT', time(), array($title));
	}

	private function add_categorie()
	{
		$cat_order = $this->request->variable('order', 0);
		$title = $this->request->variable('name_' . $this->user->lang_name, '', true);
		
		$sql_in = array(
			'cat_id'		=> $this->category->get_max_id() + 1,
			'cat_order'		=> $cat_order,
		);
		
		$sql = 'SELECT lang_id, lang_iso
			FROM ' . LANG_TABLE . "
				ORDER BY lang_id ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$iso = $row['lang_iso'];
			$lang = $this->request->variable("lang_$iso", '', true);
			$name = $this->request->variable("name_$iso", '', true);
			if ($name === '')
			{
				trigger_error($this->language->lang('SC_CATEGORY_ERROR', $this->language->lang('SC_CATEGORY_NAME')) . adm_back_link($this->u_action . '&amp;action=add'), E_USER_WARNING);
			}
			else
			{
				$sql_in = array_merge($sql_in, array(
					'cat_lang'		=> $lang,
					'cat_name'		=> $this->category->capitalize($name),
					'cat_title'		=> $this->category->capitalize($title),
				));
				$this->db->sql_query('INSERT INTO ' . $this->smilies_category_table . $this->db->sql_build_array('INSERT', $sql_in));

				if ($cat_order == 1)
				{
					$this->config->set('smilies_category_nb', $sql_in['cat_id']);
				}
			}
		}

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_ADD_CAT', time(), array($title));
		trigger_error($this->language->lang('SC_CREATE_SUCCESS') . adm_back_link($this->u_action));
	}
	
	private function edit_categorie($id)
	{
		$title = $this->request->variable('name_' . $this->user->lang_name, '', true);
		$sql = 'SELECT lang_id, lang_iso
			FROM ' . LANG_TABLE . "
				ORDER BY lang_id ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$iso = $row['lang_iso'];
			$lang = $this->request->variable("lang_$iso", '', true);
			$name = $this->request->variable("name_$iso", '', true);
			$sort = $this->request->variable("sort_$iso", '');
			$order = $this->request->variable('order', 0);
			if ($name === '')
			{
				trigger_error($this->language->lang('SC_CATEGORY_ERROR', $this->language->lang('SC_CATEGORY_NAME')) . adm_back_link($this->u_action . '&amp;action=edit&amp;id=' . $id), E_USER_WARNING);
			}
			else
			{
				if ($sort == 'edit')
				{
					$sql = 'UPDATE ' . $this->smilies_category_table . " SET cat_name = '" . $this->category->capitalize($name) . "', cat_title = '" . $this->category->capitalize($title) . "'
						WHERE cat_lang = '" . $this->db->sql_escape($lang) . "' AND cat_id = $id";
					$this->db->sql_query($sql);
				}
				else if ($sort == 'create')
				{
					$sql_in = array(
						'cat_id'		=> $id,
						'cat_order'		=> $order,
						'cat_lang'		=> $lang,
						'cat_name'		=> $this->category->capitalize($name),
						'cat_title'		=> $this->category->capitalize($title),
					);
					$this->db->sql_query('INSERT INTO ' . $this->smilies_category_table . $this->db->sql_build_array('INSERT', $sql_in));
				}
			}
		}

		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_EDIT_CAT', time(), array($title));
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
