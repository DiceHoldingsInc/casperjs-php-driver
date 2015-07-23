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

    /** @var array */
    protected $options = [];

    public function __construct()
    {
        $this->script .= <<<FRAGMENT
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug'
});

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

    public function run()
    {
        $this->script .= "
casper.run();
casper.then(function() {
    this.echo('" . Output::TAG_PAGE_CONTENT . "' + this.getHTML());
});
";
        $filename = '/tmp/php-casperjs-driver';
        file_put_contents($filename, $this->script);

        exec('casperjs ' . $filename, $output);
        unlink($filename);

        return new Output($output);
    }

    public function addOption($optionName, $value)
    {
        $this->options[$optionName] = $value;

        return $this;
    }

    public function useProxy($proxy)
    {
        $this->addOption('proxy', $proxy);

        return $this;
    }
}
