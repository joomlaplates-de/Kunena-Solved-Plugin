<?php

/**
 * Kunena Plugin - Solved
 *
 * Lets the topic author, moderators and administrators mark a Kunena 6 topic
 * as solved: the title gets a prefix, the topic icon is set to "solved",
 * optionally a notice post is added and the topic is locked.
 *
 * @package     Kunena.Plugins
 * @subpackage  Solved
 * @copyright   (C) 2026 Joomlaplates
 * @license     GNU/GPLv3 or later
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Kunena\Forum\Libraries\Forum\KunenaForum;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopic;
use Kunena\Forum\Libraries\Forum\Topic\KunenaTopicHelper;
use Kunena\Forum\Libraries\Icons\KunenaIcons;
use Kunena\Forum\Libraries\User\KunenaUserHelper;

/**
 * Class PlgKunenaSolved
 *
 * @since  1.0.0
 */
class PlgKunenaSolved extends CMSPlugin
{
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Button HTML prepared for the current topic page (injected in onAfterRender).
     *
     * @var    string
     * @since  1.0.0
     */
    private $buttonHtml = '';

    /**
     * @param   object  $subject  The object to observe
     * @param   array   $config   Plugin configuration
     *
     * @since   1.0.0
     */
    public function __construct(&$subject, $config = [])
    {
        // Do not load if Kunena is missing, too old or offline
        if (!(class_exists('Kunena\Forum\Libraries\Forum\KunenaForum') && KunenaForum::isCompatible('6.0') && KunenaForum::enabled())) {
            return;
        }

        parent::__construct($subject, $config);
    }

    /**
     * Kunena collects the topic buttons here. The bundled templates only render
     * buttons they know by name, so the HTML is prepared now and injected into
     * the page later in onAfterRender.
     *
     * @param   string  $name     Button group name
     * @param   mixed   $buttons  Registry of buttons
     * @param   object  $view     Calling controller / view
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onKunenaGetButtons($name, $buttons = null, $view = null)
    {
        if ($name !== 'topic.action' || $this->buttonHtml !== '' || empty($view->topic) || !$view->topic instanceof KunenaTopic) {
            return;
        }

        $topic = $view->topic;

        if (!$topic->exists() || !$this->canToggle($topic)) {
            return;
        }

        $solved = $this->isSolved($topic);
        $action = $solved ? 'unsolve' : 'solve';
        $return = base64_encode(Uri::getInstance()->toString());
        $url    = Route::_(
            'index.php?option=com_ajax&group=kunena&plugin=solved&format=raw&task=' . $action
            . '&id=' . (int) $topic->id . '&return=' . urlencode($return) . '&' . Session::getFormToken() . '=1'
        );

        $label = Text::_($solved ? 'PLG_KUNENA_SOLVED_BUTTON_UNSOLVE' : 'PLG_KUNENA_SOLVED_BUTTON_SOLVE');
        $class = $solved ? 'btn kbutton-solved-off' : 'btn kbutton-solved-on';
        $icon  = $solved ? KunenaIcons::undo() : KunenaIcons::check();

        $this->buttonHtml = '<a class="' . $class . ' kbutton-solved" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            . '" rel="nofollow" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
            . $icon . ' ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a> ';
    }

    /**
     * Injects the solved button into the rendered Kunena topic page.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onAfterRender()
    {
        if ($this->buttonHtml === '') {
            return;
        }

        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $body  = $app->getBody();
        $count = 0;

        // Aurelia: <div id="topic-actions"> / <div id="topic-actions-toolbar">; other templates use class="topic-actions-toolbar"
        $body = preg_replace(
            '#(<div[^>]*\b(?:id|class)="(?:[^"]*\s)?topic-actions(?:-toolbar)?(?:\s[^"]*)?"[^>]*>)#',
            '$1' . $this->escapeReplacement($this->buttonHtml),
            $body,
            -1,
            $count
        );

        // Fallback for other Kunena templates: put the button at the top of the forum output
        if (!$count) {
            $body = preg_replace(
                '#(<div[^>]*\bid="kunena"[^>]*>)#',
                '$1<div class="kunena-solved-bar mb-2">' . $this->escapeReplacement($this->buttonHtml) . '</div>',
                $body,
                1
            );
        }

        // Solid button colours; !important so template button styles (incl. hover) cannot override them
        $css = '<style>'
            . '.kbutton-solved{display:inline-flex;align-items:center;gap:.35em;color:#fff!important;border:1px solid transparent!important}'
            . '.kbutton-solved svg,.kbutton-solved i{fill:#fff!important;color:#fff!important}'
            . '.kbutton-solved-on{background:#28a745!important;border-color:#28a745!important}'
            . '.kbutton-solved-on:hover,.kbutton-solved-on:focus{background:#1e7e34!important;border-color:#1e7e34!important}'
            . '.kbutton-solved-off{background:#6c757d!important;border-color:#6c757d!important}'
            . '.kbutton-solved-off:hover,.kbutton-solved-off:focus{background:#545b62!important;border-color:#545b62!important}'
            . '</style>';
        $body = preg_replace('#</head>#i', $this->escapeReplacement($css) . '</head>', $body, 1);

        $app->setBody($body);
    }

    /**
     * Handles index.php?option=com_ajax&group=kunena&plugin=solved&task=solve|unsolve&id=X
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onAjaxSolved()
    {
        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $return = base64_decode($input->getBase64('return', ''));

        if (!$return || !Uri::isInternal($return)) {
            $return = Uri::root();
        }

        if (!class_exists('Kunena\Forum\Libraries\Forum\KunenaForum')) {
            $app->redirect($return);
        }

        if (!Session::checkToken('get')) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $app->redirect($return);
        }

        $task  = $input->getCmd('task');
        $topic = KunenaTopicHelper::get($input->getInt('id'));

        if (!$topic->exists() || !in_array($task, ['solve', 'unsolve'], true) || !$this->canToggle($topic)) {
            $app->enqueueMessage(Text::_('PLG_KUNENA_SOLVED_NOT_ALLOWED'), 'error');
            $app->redirect($return);
        }

        try {
            if ($task === 'solve' && !$this->isSolved($topic)) {
                $this->markSolved($topic);
                $app->enqueueMessage(Text::_('PLG_KUNENA_SOLVED_MARKED'), 'success');
            } elseif ($task === 'unsolve' && $this->isSolved($topic)) {
                $this->markUnsolved($topic);
                $app->enqueueMessage(Text::_('PLG_KUNENA_SOLVED_UNMARKED'), 'success');
            }
        } catch (\Throwable $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        $app->redirect($return);
    }

    /**
     * Marks the topic as solved.
     *
     * @param   KunenaTopic  $topic  Topic
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function markSolved(KunenaTopic $topic): void
    {
        $this->setSubject($topic, $this->getPrefix() . ' ' . $topic->subject);

        if ($this->params->get('set_icon', 1)) {
            $topic->icon_id = (int) $this->params->get('icon_id', 8);
        }

        $topic->save();

        if ($this->params->get('post_notice', 1)) {
            $text = trim((string) $this->params->get('notice_text', ''));

            $message = $topic->newReply(['message' => $text !== '' ? $text : Text::_('PLG_KUNENA_SOLVED_NOTICE_DEFAULT')]);
            $message->save();
        }

        if ($this->params->get('lock_topic', 0)) {
            KunenaTopicHelper::get($topic->id, true)->lock(1);
        }
    }

    /**
     * Removes the solved state from the topic.
     *
     * @param   KunenaTopic  $topic  Topic
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function markUnsolved(KunenaTopic $topic): void
    {
        $prefix = $this->getPrefix();
        $this->setSubject($topic, ltrim(mb_substr($topic->subject, mb_strlen($prefix))));

        if ($this->params->get('set_icon', 1) && (int) $topic->icon_id === (int) $this->params->get('icon_id', 8)) {
            $topic->icon_id = 0;
        }

        if ($this->params->get('lock_topic', 0)) {
            $topic->locked = 0;
        }

        $topic->save();
    }

    /**
     * Sets the subject of the topic and its first post, so editing the first
     * post later does not drop the prefix again.
     *
     * @param   KunenaTopic  $topic    Topic
     * @param   string       $subject  New subject
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function setSubject(KunenaTopic $topic, string $subject): void
    {
        $topic->subject = $subject;

        $db    = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__kunena_messages'))
            ->set($db->quoteName('subject') . ' = ' . $db->quote($subject))
            ->where($db->quoteName('id') . ' = ' . (int) $topic->first_post_id);
        $db->setQuery($query)->execute();
    }

    /**
     * @param   KunenaTopic  $topic  Topic
     *
     * @return  boolean  True if the current user may (un)mark the topic.
     *
     * @since   1.0.0
     */
    private function canToggle(KunenaTopic $topic): bool
    {
        $me = KunenaUserHelper::getMyself();

        if (!$me->exists() || $me->isBanned()) {
            return false;
        }

        $category = $topic->getCategory();

        if ($me->isAdmin($category)) {
            return (bool) $this->params->get('allow_admin', 1);
        }

        if ($me->isModerator($category)) {
            return (bool) $this->params->get('allow_moderator', 1);
        }

        return $this->params->get('allow_author', 1) && (int) $topic->first_post_userid === (int) $me->userid;
    }

    /**
     * @param   KunenaTopic  $topic  Topic
     *
     * @return  boolean
     *
     * @since   1.0.0
     */
    private function isSolved(KunenaTopic $topic): bool
    {
        $prefix = $this->getPrefix();

        // Case-insensitive, so manually prefixed titles like "[GELÖST]" count as solved too
        return mb_strtolower(mb_substr((string) $topic->subject, 0, mb_strlen($prefix))) === mb_strtolower($prefix);
    }

    /**
     * @return  string  Configured title prefix, or the language default.
     *
     * @since   1.0.0
     */
    private function getPrefix(): string
    {
        $prefix = trim((string) $this->params->get('prefix', ''));

        return $prefix !== '' ? $prefix : Text::_('PLG_KUNENA_SOLVED_PREFIX_DEFAULT');
    }

    /**
     * Escapes "$" and "\" for use in a preg_replace replacement string.
     *
     * @param   string  $text  Text
     *
     * @return  string
     *
     * @since   1.0.0
     */
    private function escapeReplacement(string $text): string
    {
        return addcslashes($text, '\\$');
    }
}
