<?php

/**
 * This file is part of Work Digital's Data Platform.
 *
 * (c) 2015 Work Digital
 */

namespace CasperJs\Driver;


/**
 * Class Output
 *
 * @author Jacopo Nardiello <jacopo@workdigital.co.uk>
 */
class Output
{
    const TAG_INFO_PHANTOMJS = '[info] [phantom]';
    const TAG_PAGE_CONTENT = '[PAGE_CONTENT]';
    const TAG_CURRENT_URL = '[CURRENT_URL]';
    const TAG_END_PAGE_CONTENT = '[info]';
    const TAG_TIMEOUT = '[TIMEOUT]';

    /** @var string[] */
    protected $statusCode;
    protected $html = '';
    protected $currentUrl;

    /**
     * @param string[] $casperConsoleOutput
     */
    public function __construct(array $casperConsoleOutput)
    {
        $this->parseCasperJsOutput($casperConsoleOutput);
    }

    protected function parseCasperJsOutput($output)
    {
        // saving html
        $htmlStartingTagFound = false;
        foreach ($output as $line) {
            // checking for timeout or generic errors
            if ($this->getErrors($line)) {
                throw new \Exception('Something went wrong');
            }

            // current url
            if ($currentUrl = $this->extractCurrentUrl($line)) {
                $this->currentUrl = $currentUrl;
            }

            // status code
            if ($statusCode = $this->extractStatusCode($line)) {
                $this->statusCode = $statusCode;
            }

            if (strpos($line, static::TAG_PAGE_CONTENT) === 0) {
                $htmlStartingTagFound = true;
                $line = substr($line, strlen(static::TAG_PAGE_CONTENT));
            }
            if (strpos($line, static::TAG_END_PAGE_CONTENT) === 0 && $htmlStartingTagFound) {
                break;
            }
            if ($htmlStartingTagFound) {
                $this->html .= $line . PHP_EOL;
            }
        }
    }

    protected function getErrors($line)
    {
        if (strpos($line, static::TAG_TIMEOUT) === 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $matches
     * @return bool|int
     */
    protected function extractStatusCode($line)
    {
        if (strpos($line, static::TAG_INFO_PHANTOMJS) === 0) {
            preg_match('~\(HTTP ([0-9]{3})\)~', $line, $matches);
            if (!empty($matches[1])) {
                return (int) $matches[1];
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function extractCurrentUrl($line)
    {
        if (strpos($line, static::TAG_CURRENT_URL) === 0) {
            return $line = substr($line, strlen(static::TAG_PAGE_CONTENT) - 1);
        }

        return false;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @return bool|int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->currentUrl;
    }
}
