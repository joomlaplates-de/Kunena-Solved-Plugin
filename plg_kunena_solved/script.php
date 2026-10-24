<?php

/**
 * Kunena Plugin - Solved
 *
 * @package     Kunena.Plugins
 * @subpackage  Solved
 * @copyright   (C) 2026 Joomlaplates
 * @license     GNU/GPLv3 or later
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Installer\InstallerScript;

/**
 * Installer script: checks minimum versions and removes files of 1.0.x.
 *
 * @since  1.1.0
 */
class PlgKunenaSolvedInstallerScript extends InstallerScript
{
    /**
     * @var    string
     * @since  1.1.0
     */
    protected $minimumPhp = '8.1.0';

    /**
     * @var    string
     * @since  1.1.0
     */
    protected $minimumJoomla = '5.0.0';

    /**
     * Files of the legacy (1.0.x) plugin structure.
     *
     * @var    array
     * @since  1.1.0
     */
    protected $deleteFiles = [
        '/plugins/kunena/solved/solved.php',
    ];

    /**
     * @param   string  $type    install, update or discover_update
     * @param   object  $parent  Installer adapter
     *
     * @return  void
     *
     * @since   1.1.0
     */
    public function postflight($type, $parent)
    {
        if ($type === 'update') {
            $this->removeFiles();
        }
    }
}
