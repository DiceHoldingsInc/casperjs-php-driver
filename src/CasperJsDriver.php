<?php

/**
 * This file is part of Work Digital's Data Platform.
 *
 * (c) 2015 Work Digital
 */

namespace CasperJs\Driver;


/**
 * Class CasperJsDriver
 *
 * @author Fabrice Guery <fabrice@workdigital.co.uk>
 */
class CasperJsDriver
{
    /** @var string */
    protected $script = '';
    /** @var  array */
    protected $output = [];

    public function __construct()
    {
        $this->script .= <<<FRAGMENT
var casper = require('casper').create();

FRAGMENT;

    }

    public function start($url, $options = null)
    {
        $options = json_encode($options);
        $this->script .= <<<FRAGMENT
casper.start().then(function() {
    this.open('$url', $options);
});
FRAGMENT;

        return $this;
    }

    public function includeHtml()
    {
        $this->script .= <<<FRAGMENT
casper.then(function() {
    this.echo(this.getHTML());
});
FRAGMENT;

        return $this;
    }

    public function run()
    {
        $this->script .= <<<FRAGMENT
casper.run();
FRAGMENT;
        $filename = '/tmp/php-casperjs-driver';
        file_put_contents($filename, $this->script);

        exec('casperjs ' . $filename, $this->output);
        unlink($filename);

        return implode("\n", $this->output);
    }

}