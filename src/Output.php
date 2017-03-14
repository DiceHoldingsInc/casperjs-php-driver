<?php

/**
 * This file is part of Work Digital's Data Platform.
 *
 * (c) 2015 Work Digital
 */

namespace CasperJs;


/**
 * Class Output
 *
 * @author Jacopo Nardiello <jacopo@workdigital.co.uk>
 */
class Output
{
    const TAG_INFO_PHANTOMJS = '[info] [phantom]';
    const TAG_PAGE_HEADERS = '[PAGE_HEADERS]';
    const TAG_PAGE_CONTENT = '[PAGE_CONTENT]';
    const TAG_CURRENT_URL = '[CURRENT_URL]';
    const TAG_END_PAGE_CONTENT = '[info]';
    const TAG_TIMEOUT = '[TIMEOUT]';

    /** @var string[] */
    protected $statusCode;
    protected $html = '';
    protected $currentUrl;
    protected $headers = [];

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
        $startRecordingHtml = false;
        foreach ($output as $line) {
            // checking for timeout or generic errors
            if ($this->extractTimedOut($line)) {
                throw new \Exception($line);
            }

            // current url
            if ($currentUrl = $this->extractCurrentUrl($line)) {
                $this->currentUrl = $currentUrl;
            }

            // status code
            if ($statusCode = $this->extractStatusCode($line)) {
                $this->statusCode = $statusCode;
            }

            // headers
            if ($headers = $this->extractHeaders($line)) {
                $this->headers = $headers;
            }

            if (strpos($line, static::TAG_PAGE_CONTENT) === 0) {
                $startRecordingHtml = true;
                $line = substr($line, strlen(static::TAG_PAGE_CONTENT));
            }
            if (strpos($line, static::TAG_END_PAGE_CONTENT) === 0 && $startRecordingHtml) {
                $startRecordingHtml = false;
            }
            if ($startRecordingHtml) {
                $this->html .= $line . PHP_EOL;
            }
        }
    }

    /**
     * @param $line
     * @return bool
     */
    protected function extractTimedOut($line)
    {
        if (strpos($line, static::TAG_TIMEOUT) === 0) {
            return $line = substr($line, strlen(static::TAG_TIMEOUT) - 1);
        }

        return false;
    }

    /**
     * @param $line
     * @return bool|int string with status code or false otherwise
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
     * @param $line
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
     * @param $line
     * @return bool|array
     */
    protected function extractHeaders($line)
    {
        if (strpos($line, static::TAG_PAGE_HEADERS) !== 0) {
            return false;
        }

        $line = substr($line, strlen(static::TAG_PAGE_HEADERS));
        $headersRaw = json_decode($line, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Tidy up the format to be name => value pairs
        $headers = [];
        foreach ($headersRaw as $v) {
            if (!empty($v['name']) && !empty($v['value'])) {
                $headers[$v['name']] = $v['value'];
            }
        }
        return $headers;
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

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
