<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020-2023 Breizh Code  https://breizhcode.com
* @license		https://opensource.org/licenses/gpl-license.php GNU Public License
* @translator	[English] Sylver35  https://breizhcode.com
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ « » “ ” …
//

$lang = array_merge($lang, array(
	'ACP_SC_SMILIES'			=> 'Smilies categories',
	'ACP_SC_SMILIES_EXPLAIN'	=> 'On this page, you can assign the categories to smilies.<br>The smilies will be displayed in a popup classified in the assigned category.<br>Smilies with no assigned category will be in the automatic category titled “Unclassified”',
	'ACP_SC_CONFIG'				=> 'Configuration of categories',
	'ACP_SC_CONFIG_EXPLAIN'		=> 'On this page, you can manage the categories of smilies.<br>Add, edit, delete categories. Change the display order and define the translations in the activated languages.',
	'SC_CATEGORY'				=> 'Category',
	'SC_CATEGORY_LANG'			=> 'Language',
	'SC_CATEGORY_DEFAUT'		=> 'Unclassified',
	'SC_CATEGORY_ID'			=> 'ID',
	'SC_CATEGORY_IN'			=> 'Category: %1$s',
	'SC_CATEGORY_NAME'			=> 'Category name',
	'SC_CATEGORY_ORDER'			=> 'Order of appearance',
	'SC_CATEGORY_ORDER_SELECT'	=> 'Category display order: ',
	'SC_CATEGORY_ANY'			=> 'All the categories',
	'SC_CATEGORY_ANY_CREATE'	=> 'No category has been created',
	'SC_CATEGORY_ADD'			=> 'Add category',
	'SC_CATEGORY_EDIT'			=> 'Edit Category',
	'SC_CATEGORY_EDIT_LIST'		=> 'Modify selection of smileys',
	'SC_CATEGORY_SELECT'		=> 'Select a category',
	'SC_LANGUAGE_EMPTY'			=> [
		1	=> 'This category is not translated into the language : ',
		2	=> 'This category is not translated into the languages :  ',
	],
	'SC_CREATE_SUCCESS'			=> 'The category has been created',
	'SC_DELETE_SUCCESS'			=> 'The category has been deleted',
	'SC_EDIT_SUCCESS'			=> 'The category has been edited',
	'SC_MOVE_SUCCESS'			=> 'The category has been moved',
	'SC_MOVE_ERROR'				=> 'An error has occurred',
	'SC_SMILIES'				=> [
		1	=> '%2$s smilie',
		2	=> '%2$s smilies',
	],
	'SC_SMILIES_IN'				=> 'smilies',
	'SC_SMILIES_PAGE'			=> 'Page N° ',
	'SC_SMILIES_PAGE_TITLE'		=> 'page %1$s/%2$s',
	'SC_SMILIES_ANY_CATEGORY'	=> 'No smiley has an assigned category',
	'SC_SMILIES_EMPTY'			=> 'You have not selected any smiley',
	'SC_SMILIES_EMPTY_CATEGORY'	=> 'This category contains no smiley',
	'SC_SMILIES_NO_CATEGORY'	=> 'The following smilies have no category assigned',
	'SC_CATEGORY_ERROR'			=> 'You must fill all the fields “Category name”',
	'SC_CATEGORY_TITLE'			=> 'More smilies in categories',
	'SC_SMILIES_TITLE'			=> 'See more smilies ranked in categories',
	'SC_SMILIES_CLOSE'			=> 'Close the panel',
	'SC_CONFIG_TITLE'			=> 'Configuration',
	'SC_CONFIG_PAGE'			=> 'Smilies per page',
	'SC_CONFIG_EXPLAIN'			=> 'Indicate here the number of smilies per page displayed for the categories',
	'SC_CONFIG_PAGE_ACP'		=> 'Smilies per page ACP',
	'SC_CONFIG_ACP_EXPLAIN'		=> 'Indicate here the number of smilies per page displayed in the smilies category management page',
	'LOG_SC_CONFIG'				=> '<strong>Update of the Smilies categories configuration</strong>',
	'LOG_SC_ADD_CAT'			=> '<strong>Creation of a smilies category </strong> » %s',
	'LOG_SC_EDIT_CAT'			=> '<strong>Editing a smilies category </strong> » %s',
	'LOG_SC_MOVE_UP_CAT'		=> '<strong>Ascent of a smilies category </strong> » %s',
	'LOG_SC_MOVE_DOWN_CAT'		=> '<strong>Descending a smilies category </strong> » %s',
	'LOG_SC_DELETE_CAT'			=> '<strong>Deleting a smilies category </strong> » %s',
	'SC_VERSION_COPY'			=> '<a href="%1$s" onclick="window.open(this.href);return false;">Breizh Smilies Categories v%2$s</a> © 2023 - Breizhcode - The Breizh Touch',
));
