<?php

namespace AbuseIO\Parsers;

use AbuseIO\Models\Incident;
use PhpMimeMailParser\Parser as MimeParser;

/**
 * Class Shadowserver
 * @package AbuseIO\Parsers
 */
class Jmrp extends Parser
{
    /**
     * Create a new Shadowserver instance
     *
     * @param \PhpMimeMailParser\Parser $parsedMail phpMimeParser object
     * @param array $arfMail array with ARF detected results
     */
    public function __construct($parsedMail, $arfMail)
    {
        parent::__construct($parsedMail, $arfMail, $this);
    }

    /**
     * Parse attachments
     * @return array    Returns array with failed or success data
     *                  (See parser-common/src/Parser.php) for more info.
     */
    public function parse()
    {
        $this->feedName = 'default';

        $attachments = $this->parsedMail->getAttachments();
        $arfMail = [];

        foreach ($attachments as $attachment) {
            if ($attachment->getContentType() == 'message/feedback-report') {
                $this->arfMail['report'] = $attachment->getContent();
            }

            if ($attachment->getContentType() == 'message/rfc822') {
                $this->arfMail['evidence'] = utf8_encode($attachment->getContent());
            }

            if ($attachment->getContentType() == 'text/plain') {
                $this->arfMail['message'] = $attachment->getContent();
            }
        }

        preg_match(
            '/complaint about message from (?<address>.*)/',
            $this->parsedMail->getHeader('subject'),
            $subjMatches
        );

        if (!filter_var($subjMatches['address'], FILTER_VALIDATE_IP)) {
            return $this->failed("Invalid IP returned from regex");
        }

        $spamMessage = new MimeParser();
        $spamMessage->setText($this->arfMail['evidence']);

        $report = [];
        $report['headers'] = $spamMessage->getHeaders();
        $report['body'] = $spamMessage->getMessageBody();
        $report['source-ip'] = $subjMatches['address'];

        // Sanity check
        if ($this->hasRequiredFields($report) === true) {

            // incident has all requirements met, filter and add!
            $report = $this->applyFilters($report);

            $incident = new Incident();
            $incident->source      = config("{$this->configBase}.parser.name");
            $incident->source_id   = false;
            $incident->ip          = $subjMatches['address'];
            $incident->domain      = false;
            $incident->class       = config("{$this->configBase}.feeds.{$this->feedName}.class");
            $incident->type        = config("{$this->configBase}.feeds.{$this->feedName}.type");
            $incident->timestamp   = strtotime($this->parsedMail->getHeader('date'));
            $incident->information = json_encode($report);

            $this->incidents[] = $incident;

        }

        return $this->success();
    }
}

