<?php

namespace CasperJs\Driver;

/**
 * @author jacopo.nardiello
 */
class OutputTest extends \PHPUnit_Framework_TestCase
{
    public function testOutputWillExtractHtml()
    {
        $casperOutput = [
            Output::TAG_PAGE_CONTENT . "<!DOCTYPE html><html><head>",
            "        <title>Simplest possible page</title>"
        ];

        $expectedOutput = "<!DOCTYPE html><html><head>\n" .
            "        <title>Simplest possible page</title>\n";
        $output = new Output($casperOutput);

        $this->assertEquals($expectedOutput, $output->getHtml());
    }

    public function testHtmlWillOnlyReturnPageContent()
    {
        $casperOutput = [
            "[info] [phantom] Phantom is trolling me!",
            Output::TAG_PAGE_CONTENT . "<!DOCTYPE html><html><head>",
            "        <title>Simplest possible page</title>",
        ];

        $expectedOutput = "<!DOCTYPE html><html><head>\n" .
            "        <title>Simplest possible page</title>\n";
        $output = new Output($casperOutput);

        $this->assertEquals($expectedOutput, $output->getHtml());
    }

    /**
     * @expectedException \Exception
     */
    public function testWillOutputExceptionForTimeout()
    {
        $casperOutput = [
            "[info] [phantom] Phantom is trolling me!",
            Output::TAG_TIMEOUT,
            Output::TAG_PAGE_CONTENT . "<!DOCTYPE html><html><head>",
            "        <title>Simplest possible page</title>",
        ];

        $output = new Output($casperOutput);
    }
}
