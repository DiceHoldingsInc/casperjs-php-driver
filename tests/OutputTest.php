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
            "<!DOCTYPE html><html><head>",
            "        <title>Simplest possible page</title>"
        ];
        $expectedOutput = implode("\n", $casperOutput);
        $output = new Output($casperOutput);

        $this->assertEquals($expectedOutput, $output->getHtml());
    }
}
