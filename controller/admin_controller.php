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

					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_' . strtoupper($action), time(), array($title));
					trigger_error($this->language->lang('SC_CREATE_SUCCESS') . adm_back_link($this->u_action));

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

					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_' . strtoupper($action), time(), array($title));
					trigger_error($this->language->lang('SC_EDIT_SUCCESS') . adm_back_link($this->u_action));

				break;

				case 'move_up':
				case 'move_down':

					if (!check_link_hash($this->request->variable('hash', ''), 'acp-main_module'))
					{
						trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
					}

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
						break;
					}

					$max_order = $this->category->get_max_order();

					if ($current_order == $max_order && $action == 'move_down')
					{
						break;
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

						if ($switch_order_id == 1)
						{
							$this->config->set('smilies_category_nb', $id);
						}
					}

					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_' . strtoupper($action) . '_CAT', time(), array($title));

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
						$sql = 'SELECT cat_title
							FROM ' . $this->smilies_category_table . "
								WHERE cat_id = $id";
						$result = $this->db->sql_query($sql);
						$row = $this->db->sql_fetchrow($result);
						$title = $row['cat_title'];
						$this->db->sql_freeresult($result);

						$sql_delete = 'DELETE FROM ' . $this->smilies_category_table . " WHERE cat_id = $id";
						$this->db->sql_query($sql_delete);

						// Reset appropriate smilies category id
						$sql_update = 'UPDATE ' . SMILIES_TABLE . " SET category = 0 WHERE category = $id";
						$this->db->sql_query($sql_update);

						$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_SC_' . strtoupper($action) . '_CAT', time(), array($title));

						if ($this->request->is_ajax())
						{
							trigger_error($this->language->lang('SC_DELETE_SUCCESS') . adm_back_link($this->u_action));
							$json_response = new \phpbb\json_response;
							$json_response->send(array(
								'MESSAGE_TITLE'	=> $this->language->lang('INFORMATION'),
								'MESSAGE_TEXT'	=> $this->language->lang('SC_DELETE_SUCCESS'),
								'REFRESH_DATA'	=> array('time'		=> 2),
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
		$lang = $this->user->lang_name;
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
			$this->category->extract_list_smilies($select, $start, $this->u_action);
		}

		$this->template->assign_vars(array(
			'CATEGORIE_SMILIES'		=> true,
		));
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
