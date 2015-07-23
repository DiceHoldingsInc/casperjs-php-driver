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
    const INFO_PHANTOMJS = '[info] [phantom]';

    /** @var string[] */
    protected $output;

    /**
     * @param string[] $casperConsoleOutput
     */
    public function __construct(array $casperConsoleOutput)
    {
        $this->output = $casperConsoleOutput;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return implode("\n", $this->output);
    }

    /**
     * @return bool|int
     */
    public function getStatusCode()
    {
        foreach ($this->output as $line) {
            if (strpos($line, static::INFO_PHANTOMJS) === 0) {
                preg_match('~\(HTTP ([0-9]{3})\)~', $line, $matches);
                if (!empty($matches[1])) {
                    return (int) $matches[1];
                }
            }
        }

        return false;
    }
}
