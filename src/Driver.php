<?php

/**
 * This file is part of Work Digital's Data Platform.
 *
 * (c) 2015 Work Digital
 */

namespace CasperJs;

use CasperJs\Input\OptionsCliBuilder;

/**
 * Class CasperJsDriver
 *
 * @author Fabrice Guery <fabrice@workdigital.co.uk>
 */
class Driver
{
    /** @var string */
    protected $script = '';

    /** @var OptionsCliBuilder */
    protected $optionBuilder;

    protected $jsInteraction;

    public function __construct()
    {
        $this->optionBuilder = new OptionsCliBuilder();
        $this->script .= "
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  colorizerType: 'Dummy'
});

";

    }

    public function start($url, $options = null)
    {
        $options = json_encode($options);
        $this->script .= "
casper.start().then(function() {
    this.open('$url', $options);
});
";

        return $this;
    }

    public function run()
    {
        $this->script .= "
casper.run();
casper.then(function() {
    this.echo('" . Output::TAG_CURRENT_URL . "' + this.getCurrentUrl());
    this.echo('" . Output::TAG_PAGE_CONTENT . "' + this.getHTML());
});";
        $filename = tempnam(null, 'php-casperjs-');
        file_put_contents($filename, $this->script);

        exec('casperjs ' . $filename . $this->optionBuilder->build(), $output);
        unlink($filename);

        return new Output($output);
    }

    public function addOption($optionName, $value)
    {
        $this->optionBuilder->addOption($optionName, $value);

        return $this;
    }

    public function useProxy($proxy)
    {
        $this->addOption('proxy', $proxy);

        return $this;
    }

    public function setUserAgent($userAgent)
    {
        $this->script .= "casper.userAgent('$userAgent');";

        return $this;
    }

    public function evaluate($script)
    {
        $this->script .= "
casper.then(function() {
    casper.evaluate(function() {
        $script
    });
});";

        return $this;
    }

    public function waitForSelector($selector, $timeout)
    {
        $this->script .= "
casper.waitForSelector(
    '$selector',
    function () {
        this.echo('found selector \"$selector\"');
    },
    function () {
        this.echo('" . Output::TAG_TIMEOUT . " $($selector) not found after $timeout ms');
    },
    $timeout
);";

        return $this;
    }

    public function setViewPort($width, $height)
    {
        $this->script .= "
casper.then(function () {
    this.viewport($width, $height);
});";

        return $this;
    }

    public function wait($timeout)
    {
        $this->script .= "
casper.wait(
    $timeout,
    function () {
        this.echo('" . Output::TAG_TIMEOUT . " after waiting $timeout ms');
    }
);";
        return $this;
    }

    public function click($selector)
    {
        $this->script .= "
casper.then(function() {
    this.click('$selector');
});";

        return $this;
    }

    public function setAcceptLanguage(array $langs)
    {
        $this->setHeaders([
            'Accept-Language' => ['en-US'],
        ]);

        return $this;
    }

    public function setHeaders(array $headers)
    {
        $headersScript = "
casper.page.customHeaders = {
";

        if (!empty($headers)) {
            $headerLines = [];
            foreach ($headers as $header => $value) {
                $headerLine = "    '{$header}': '";
                $headerLine .= (is_array($value)) ? implode(',', $value) : $value;
                $headerLine .= "'";
                $headerLines[] = $headerLine;
            }
            $headersScript .= implode(",\n", $headerLines) . "\n";
        }


        $headersScript .= "};";

        $this->script .= $headersScript;

        return $this;
    }

    /**
     * Should only be used for testing purpose, until a "one day" refactor.
     *
     * @return string
     */
    public function getScript()
    {
        return $this->script;
    }
}
