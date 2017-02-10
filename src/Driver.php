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
    
    protected $casperJsCommandPath;

    public function __construct($casperJsCommandPath = 'casperjs')
    {
        if (!$this->isCommandExecutable($casperJsCommandPath)) {
            throw new \Exception(
                'Unable to execute ' . $casperJsCommandPath . '. '
                . 'Ensure file exists in $PATH and exec() function is available.'
            );
        }
        
        $this->casperJsCommandPath = $casperJsCommandPath;
        
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

        exec($this->casperJsCommandPath . ' ' . $filename . $this->optionBuilder->build(), $output);
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

    public function clickXpath($selector)
    {
        $this->script .= "
var x = require('casper').selectXpath;

casper.then(function() {
    this.click(x('$selector'));
});";

        return $this;
    }

    public function setAcceptLanguage(array $langs = [])
    {
        if (empty($langs)) {
            $langs = ['en-US'];
        }

        $this->setHeaders([
            'Accept-Language' => $langs,
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
                // Current version of casperjs will not decode gzipped output
                if ($header == 'Accept-Encoding') {
                    continue;
                }
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
     * Disable images loading to save a bit of bandwidth/speed.
     * @return $this
     */
    public function disableImageLoading()
    {
        $this->script .= '
casper.options.pageSettings.loadImages = false;
';
        return $this;
    }

    /**
     * Disable plugin loading to save a bit of bandwidth/speed.
     * @return $this
     */
    public function disablePluginLoading()
    {
        $this->script .= '
casper.options.pageSettings.loadPlugins = false;
';
        return $this;
    }

    /**
     * Define a timeout in ms for each casper step.
     * It's used as an attempt to fix some crawls "hanging"
     *
     * @param int $timeout
     * @return $this
     */
    public function setStepTimeout($timeout = 30000)
    {
        $this->script .= '
casper.options.stepTimeout = ' . $timeout .';
';
        return $this;
    }

    /**
     * Avoid to trigger requests that match certain URLs.
     * Useful, for example, to avoid triggering requests to GoogleAnalytics, CSS etc.
     *
     * @param string[] $urlJsRegexes
     * @return $this
     */
    public function setRequestsToSkip($urlJsRegexes)
    {
        $this->script .= '
casper.options.onResourceRequested = function(casper, requestData, request) {';

        foreach ($urlJsRegexes as $regex) {
            $this->script .= '
    if ((' . $regex . ').test(requestData.url)) {
        console.log("SKIPPING " + requestData.url);
        request.abort();
    }
';
        }
        $this->script .= '}
';

        return $this;
    }

    /**
     * @param string $imagePath
     * @return $this
     * @throws \Exception
     */
    public function capture($imagePath)
    {
        if (is_dir($imagePath)) {
            throw new \Exception('Unable to open ' . $imagePath);
        }
        $file = @fopen($imagePath, 'w');
        if (!$file) {
            throw new \Exception('Unable to open ' . $imagePath);
        }
        fclose($file);

        $this->script .= '
casper.then(function() {
    this.capture(\'' . $imagePath . '\');
});
';

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

    protected function isCommandExecutable($command)
    {
        if (!function_exists('exec')) {
            return false;
        }
        exec('which ' . escapeshellarg($command), $output);
        if (!$output) {
            return false;
        }


        return true;
    }
}
