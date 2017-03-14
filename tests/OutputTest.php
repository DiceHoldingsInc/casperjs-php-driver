<?php

/**
 * @author jacopo.nardiello
 */
class OutputTest extends \PHPUnit_Framework_TestCase
{
    private function newOutput($casperConsoleOutput)
    {
        return new CasperJs\Output($casperConsoleOutput);
    }

    public function testOutputWillExtractHtml()
    {
        $casperOutput = [
            CasperJs\Output::TAG_PAGE_CONTENT . "<!DOCTYPE html><html><head>",
            "        <title>Simplest possible page</title>"
        ];
        $expectedOutput = "<!DOCTYPE html><html><head>\n" .
                          "        <title>Simplest possible page</title>\n";

        $output = $this->newOutput($casperOutput);

        $this->assertEquals($expectedOutput, $output->getHtml());
    }

    public function testDriverShouldBeAbleToRetrieveStatusCode()
    {
        $output = $this->newOutput([
            '[info] [phantom] Starting...',
            '[info] [phantom] Running suite: 2 steps',
            '[info] [phantom] Step anonymous 1/3: done in 37ms.',
            '[info] [phantom] Step anonymous 2/3 https://www.google.co.uk/?gfe_rd=cr&ei=1_awVZm1Nc2q8we32aj4CA (HTTP 200)',
            '<!DOCTYPE html><html itemscope="" itemtype="http://schema.org/WebPage" lang="en-GB"><head><meta content="/images/google_favicon_128.png" itemprop="image"><title>Google</title>',
            '[info] [phantom] Step anonymous 2/3: done in 1275ms.',
            '[info] [phantom] Step anonymous 3/3 https://www.google.co.uk/?gfe_rd=cr&ei=1_awVZm1Nc2q8we32aj4CA (HTTP 200)',
            '[info] [phantom] Step anonymous 3/3: done in 1277ms.',
            '[info] [phantom] Done 3 steps in 1295ms',
        ]);

        $this->assertEquals(200, $output->getStatusCode());
    }

    public function testHtmlWillOnlyReturnPageContent()
    {
        $casperOutput = [
            "[info] [phantom] Phantom is trolling me!",
            CasperJs\Output::TAG_PAGE_CONTENT . "<!DOCTYPE html><html><head>",
            "        <title>Simplest possible page</title>",
        ];
        $expectedOutput = "<!DOCTYPE html><html><head>\n" .
                          "        <title>Simplest possible page</title>\n";

        $output = $this->newOutput($casperOutput);

        $this->assertEquals($expectedOutput, $output->getHtml());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage [TIMEOUT] Message example.
     */
    public function testWillOutputExceptionWithMessageForTimeout()
    {
        $casperOutput = [
            "[info] [phantom] Phantom is trolling me!",
            CasperJs\Output::TAG_TIMEOUT . ' Message example.',
            CasperJs\Output::TAG_PAGE_CONTENT . "<!DOCTYPE html><html><head>",
            "        <title>Simplest possible page</title>",
        ];

        $output = $this->newOutput($casperOutput);
    }

    public function testShouldReturnCurrentUrl()
    {
        $casperOutput = [
            "[info] [phantom] Starting...",
            "[info] [phantom] Running suite: 1 step",
            "[debug] [phantom] Successfully injected Casper client-side utilities",
            CasperJs\Output::TAG_CURRENT_URL . "http://someurl.something.com/",
            CasperJs\Output::TAG_PAGE_CONTENT . "<html><head></head><body><pre style='word-wrap: break-word; white-space: pre-wrap;'>",
            "[info] [phantom] Step anonymous 2/2: done in 266ms.",
        ];
        $expectedOutput = "http://someurl.something.com/";

        $output = $this->newOutput($casperOutput);

        $this->assertEquals($expectedOutput, $output->getCurrentUrl());
    }

    public function testReturnsPageHeaders()
    {
        $casperOutput = [
            "[info] [phantom] Starting...",
            "[info] [phantom] Running suite: 1 step",
            "[debug] [phantom] Successfully injected Casper client-side utilities",
            CasperJs\Output::TAG_CURRENT_URL . "http://someurl.something.com/",
            CasperJs\Output::TAG_PAGE_HEADERS . json_encode([['name' => 'test-header', 'value' => 'my value']]),
            CasperJs\Output::TAG_PAGE_CONTENT . "<html><head></head><body><pre style='word-wrap: break-word; white-space: pre-wrap;'>",
            "[info] [phantom] Step anonymous 2/2: done in 266ms.",
        ];
        $expectedOutput = "http://someurl.something.com/";

        $output = $this->newOutput($casperOutput);

        $this->assertEquals([
            'test-header' => 'my value'
        ], $output->getHeaders());
    }

    public function testDoesNotReturnsPageHeadersWhenInvalidJson()
    {
        $casperOutput = [
            "[info] [phantom] Starting...",
            "[info] [phantom] Running suite: 1 step",
            "[debug] [phantom] Successfully injected Casper client-side utilities",
            CasperJs\Output::TAG_CURRENT_URL . "http://someurl.something.com/",
            CasperJs\Output::TAG_PAGE_HEADERS . 'invalidjson',
            CasperJs\Output::TAG_PAGE_CONTENT . "<html><head></head><body><pre style='word-wrap: break-word; white-space: pre-wrap;'>",
            "[info] [phantom] Step anonymous 2/2: done in 266ms.",
        ];
        $expectedOutput = "http://someurl.something.com/";

        $output = $this->newOutput($casperOutput);

        $this->assertEquals([], $output->getHeaders());
    }
}
