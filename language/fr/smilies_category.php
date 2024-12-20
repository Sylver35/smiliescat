<?php
/**
*
* @package		Breizh Smilies Categories Extension
* @copyright	(c) 2020-2024 Breizh Code  https://breizhcode.com
* @license		https://opensource.org/licenses/gpl-license.php GNU Public License
* @translator	[French] Sylver35  https://breizhcode.com
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
// ’ « » “ ” …
//

$lang = array_merge($lang, array(
	'ACP_SC_SMILIES'			=> 'Catégories des smileys',
	'ACP_SC_SMILIES_EXPLAIN'	=> 'Sur cette page, vous pouvez attribuer les catégories des smileys.<br>Les smileys seront affichés dans une popup classés dans la catégorie attribuée.<br>Les smileys n’ayant pas de catégorie attribuée seront dans la catégorie automatique intitulée “Non classés”.<br>Les smileys non affichés seront dans la catégorie spéciale "Non affichés".',
	'ACP_SC_CONFIG'				=> 'Configuration des catégories',
	'ACP_SC_CONFIG_EXPLAIN'		=> 'Sur cette page, vous pouvez gérer les catégories des smileys.<br>Les catégories en orange ne seront pas affichées car ne contiennent aucun smiley.<br>Ajouter, éditer, supprimer des catégories. Modifier l’ordre d’affichage et définir les traductions dans les langues activées.',
	'SC_CATEGORY'				=> 'Catégorie',
	'SC_CATEGORY_LANG'			=> 'Langue',
	'SC_CATEGORY_DEFAUT'		=> 'Non classés',
	'SC_CATEGORY_NOT'			=> 'Non affichés',
	'SC_CATEGORY_ID'			=> 'ID',
	'SC_CATEGORY_IN'			=> 'Catégorie : %1$s',
	'SC_CATEGORY_NAME'			=> 'Nom de la catégorie',
	'SC_CATEGORY_ORDER'			=> 'Ordre d’affichage',
	'SC_CATEGORY_ORDER_SELECT'	=> 'Ordre d’affichage de la catégorie : ',
	'SC_CATEGORY_ANY'			=> 'Toutes les catégories',
	'SC_CATEGORY_ANY_CREATE'	=> 'Aucune catégorie n’a été créée',
	'SC_CATEGORY_ADD'			=> 'Ajouter une catégorie',
	'SC_CATEGORY_EDIT'			=> 'Modifier la catégorie',
	'SC_CATEGORY_EDIT_LIST'		=> 'Modifier la sélection de smileys',
	'SC_CATEGORY_SELECT'		=> 'Sélectionner une catégorie',
	'SC_LANGUAGE_EMPTY'			=> [
		1	=> 'Cette catégorie n’est pas traduite dans la langue : ',
		2	=> 'Cette catégorie n’est pas traduite dans les langues :  ',
	],
	'SC_CREATE_SUCCESS'			=> 'La catégorie a bien été créée',
	'SC_DELETE_SUCCESS'			=> 'La catégorie a bien été supprimée',
	'SC_EDIT_SUCCESS'			=> 'La catégorie a bien été éditée',
	'SC_MOVE_SUCCESS'			=> 'La catégorie a bien été déplacée',
	'SC_MOVE_ERROR'				=> 'Une erreur s’est produite',
	'SC_NEXT'					=> '»',
	'SC_PREVIOUS'				=> '«',
	'SC_PREVIOUS_TITLE'			=> 'Précédente',
	'SC_NEXT_TITLE'				=> 'Suivante',
	'SC_SMILIES'				=> [
		1	=> '%2$s smiley',
		2	=> '%2$s smileys',
	],
	'SC_SMILIES_IN'				=> 'smileys',
	'SC_SMILIES_PAGE'			=> 'Page N° ',
	'SC_SMILIES_PAGE_TITLE'		=> 'page %1$s/%2$s',
	'SC_SMILIES_ANY_CATEGORY'	=> 'Aucun smiley n’a de catégorie attribuée',
	'SC_SMILIES_EMPTY'			=> 'Vous n’avez sélectionné aucun smiley',
	'SC_SMILIES_EMPTY_CATEGORY'	=> 'Cette catégorie ne contient aucun smiley',
	'SC_SMILIES_NO_CATEGORY'	=> 'Les smileys suivants n’ont pas de catégorie attribuée',
	'SC_CATEGORY_ERROR'			=> 'Vous devez remplir tous les champs “Nom de la catégorie”',
	'SC_CATEGORY_TITLE'			=> 'Plus de smileys en catégories',
	'SC_SMILIES_TITLE'			=> 'Voir plus de smileys classés en catégories',
	'SC_SMILIES_CLOSE'			=> 'Refermer le panneau',
	'SC_SMILIES_CLOSE_WINDOW'	=> 'Fermer la fenètre',
	'SC_CONFIG_TITLE'			=> 'Configuration',
	'SC_CONFIG_PAGE'			=> 'Smileys par page',
	'SC_CONFIG_EXPLAIN'			=> 'Indiquez ici le nombre de smileys par page affichés pour les catégories',
	'SC_CONFIG_PAGE_ACP'		=> 'Smileys par page ACP',
	'SC_CONFIG_ACP_EXPLAIN'		=> 'Indiquez ici le nombre de smileys par page affichés dans la page de gestion des catégories des smileys',
	'LOG_SC_CONFIG'				=> '<strong>Mise à jour de la configuration Catégories des smileys</strong>',
	'LOG_SC_ADD_CAT'			=> '<strong>Création d’une catégorie de smileys </strong> » %s',
	'LOG_SC_EDIT_CAT'			=> '<strong>Édition d’une catégorie de smileys </strong> » %s',
	'LOG_SC_MOVE_UP_CAT'		=> '<strong>Remontée d’une catégorie de smileys </strong> » %s',
	'LOG_SC_MOVE_DOWN_CAT'		=> '<strong>Descente d’une catégorie de smileys </strong> » %s',
	'LOG_SC_DELETE_CAT'			=> '<strong>Suppression d’une catégorie de smileys </strong> » %s',
	'LOG_SC_DELETE_CAT_LANG'	=> '<strong>Suppression des catégories de smileys de langue </strong><br>» %s » “%s”',
	'SC_VERSION_COPY'			=> '<a href="%1$s" onclick="window.open(this.href);return false;">Breizh Smilies Categories v%2$s</a> © 2024 - Breizhcode - The Breizh Touch',
));
