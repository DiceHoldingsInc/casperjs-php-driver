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
    protected $output;

    public function __construct($casperConsoleOutput)
    {
        $this->output = $casperConsoleOutput;
    }

    public function getHtml()
    {
        return implode("\n", $this->output);
    }

    public function getStatusCode()
    {

    }
}
