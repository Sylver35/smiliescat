<?php
/**
 *
 * @package		Breizh Smilies Categories Extension
 * @copyright	(c) 2020-2021 Sylver35  https://breizhcode.com
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

namespace sylver35\smiliescat;

/**
* @ignore
*/

/**
 * Class ext
 *
 */
class ext extends \phpbb\extension\base
{
	/**
	 * Check whether or not the extension can be enabled.
	 * The current phpBB version should meet or exceed
	 * the minimum version required by this extension:
	 *
	 * Requires phpBB 3.3.4 and PHP 7.1.3
	 *
	 * @return bool
	 * @access public
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');

		return phpbb_version_compare($config['version'], '3.3.4', '>=') && version_compare(PHP_VERSION, '7.1.3', '>=');
	}
}
