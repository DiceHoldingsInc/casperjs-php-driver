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
    const TAG_END_PAGE_CONTENT = '[info]';
    const TAG_TIMEOUT = '[TIMEOUT]';

    /** @var string[] */
    protected $output;

    /**
     * @param string[] $casperConsoleOutput
     */
    public function __construct(array $casperConsoleOutput)
    {
        $this->output = $casperConsoleOutput;

        if ($this->getErrors()) {
            throw new \Exception('Something went wrong');
        }
    }

    protected function getErrors()
    {
        foreach ($this->output as $line) {
            if (strpos($line, static::TAG_TIMEOUT) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        $found = false;
        $html = '';
        foreach ($this->output as $line) {
            if (strpos($line, static::TAG_PAGE_CONTENT) === 0) {
                $found = true;
                $line = substr($line, strlen(static::TAG_PAGE_CONTENT));
            }
            if (strpos($line, static::TAG_END_PAGE_CONTENT) === 0 && $found) {
                break;
            }
            if ($found) {
                $html .= $line . PHP_EOL;
            }
        }
        return $html;
    }

    /**
     * @return bool|int
     */
    public function getStatusCode()
    {
        foreach ($this->output as $line) {
            if (strpos($line, static::TAG_INFO_PHANTOMJS) === 0) {
                preg_match('~\(HTTP ([0-9]{3})\)~', $line, $matches);
                if (!empty($matches[1])) {
                    return (int) $matches[1];
                }
            }
        }

        return false;
    }
}
