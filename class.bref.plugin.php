<?php if (!defined('APPLICATION')) {
    exit();
}
/*
  Copyright 2008, 2009 Vanilla Forums Inc.
  This file is part of Garden.
  Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
  Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
  You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
  Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
 */

$PluginInfo['BREF'] = array(
  'Description' => 'Bref Formatter for Vanilla',
  'Version' => '0.0.1',
  'RequiredApplications' => array('Vanilla' => '2.1.8p2'),
  'RequiredTheme' => false,
  'RequiredPlugins' => false,
    # 'SettingsUrl' => 'dashboard/settings/emojify',
  'HasLocale' => false,
  'Author' => "GyD",
  'AuthorEmail' => 'contact@gyd.be',
  'AuthorUrl' => 'https://github.com/GyD'
);

/**
 * Class emojify
 */
class BREFPlugin extends Gdn_Plugin
{
    /**
     * Contain parsing status
     * @var
     */
    private $parsed = false;

    private $Apc = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (function_exists('apc_fetch') && C('Garden.Apc', false)) {
            $this->Apc = true;
        }

        parent::__construct();
    }

    /**
     * Parsedown formatter (and future supported formatters) can be called before BeforeDiscussionRender event
     *   so be sure to reset parsed flag
     *
     * @param $Sender
     */
    public function DiscussionController_BeforeDiscussionRender_Handler($Sender)
    {
        $this->setParsed(false);
    }

    /**
     * Reset isParsed flag after discussion body is rendered
     *
     * @param $Sender
     */
    public function DiscussionController_AfterDiscussionBody_Handler($Sender)
    {
        $this->setParsed(false);
    }

    /**
     * Reset isParsed flag after comment body is rendered
     *
     * @param $Sender
     */
    public function DiscussionController_AfterCommentBody_Handler($Sender)
    {
        $this->setParsed(false);
    }


    /**
     * @param $Sender
     */
    public function ParsedownPlugin_BeforeFormat_Handler($Sender)
    {
        if (!$this->isParsed()) {
            $this->applyBREF($Sender->EventArguments['Result']);
            $this->setParsed(true);
        }
    }


    /**
     * Replace emojify short code in comments.
     *
     * @param $Sender
     */
    public function DiscussionController_BeforeCommentBody_Handler($Sender)
    {
        if (!$this->isParsed()) {
            $this->applyBREF($Sender->EventArguments[GetValueR('EventArguments.Type', $Sender)]->Body);
            $this->setParsed(true);
        }
    }

    /**
     * Is body parsed
     *
     * @return bool
     */
    private function isParsed()
    {
        return $this->parsed;
    }

    /**
     * Set body as parsed
     *
     * @param bool $parsed
     */
    private function setParsed($parsed = true)
    {
        $this->parsed = $parsed;
    }

    /**
     * Convert Emoji to short code in preview
     *
     * @param $Sender
     */
    public function Base_AfterCommentPreviewFormat_Handler($Sender)
    {
        if (!$this->isParsed()) {
            $this->applyBREF($Sender->Comment->Body);
            $this->setParsed(true);
        }
    }

    /**
     * Convert Emoji to short code in preview
     *
     * @param $Sender
     */
    public function Base_BeforeCommentPreviewFormat_Handler($Sender)
    {
        $this->applyBREF($Sender->Comment->Body);
    }

    /**
     * @return \GyD\BREFormatter\BREF
     */
    private function getFormatter()
    {
        static $formatter;

        if (null === $formatter) {
            require __DIR__ . '/lib/BREFormatter/BREF.php';

            $formatter = new \GyD\BREFormatter\BREF();
            if ($this->Apc) {
                $cache = apc_fetch('BREF_parsed_urls');
                if (!empty($cache) && is_array($cache)) {
                    $formatter->setCache($cache);
                }
            }
        }

        return $formatter;
    }

    /**
     * @param $body
     */
    private function applyBREF(&$body)
    {
        Gdn::Config()->Set('Garden.Format.YouTube', 0, true, false);
        Gdn::Config()->Set('Garden.Format.Vimeo', 0, true, false);
        $body = $this->getFormatter()->format($body);

        if ($this->Apc) {
            apc_store('BREF_parsed_urls', $this->getFormatter()->getCache());
        }
    }
}