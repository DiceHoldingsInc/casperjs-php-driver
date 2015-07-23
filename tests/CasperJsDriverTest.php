<?php

namespace CasperJs\Driver;

/**
 * @author jacopo.nardiello
 */
class CasperJsDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testDriverWillLoadSimplePage()
    {
        $driver = new CasperJsDriver();

        $output = $driver->start('file://' . __DIR__ . '/fixtures/simpleHtml.html')
            ->run();

        $this->assertInstanceOf('\\CasperJs\\Driver\\Output', $output);
        $this->assertContains('Pizza with ketchup', $output->getHtml());
    }

    public function testDriverShouldBeAbleToRetrieveStatusCode()
    {
        $driver = $this->getMockBuilder('CasperJs\Driver\CasperJsDriver')
            ->setMethods(['run'])
            ->getMock();

        $driver->method('run')
            ->willReturn(new Output([
                '[info] [phantom] Starting...',
                '[info] [phantom] Running suite: 2 steps',
                '[info] [phantom] Step anonymous 1/3: done in 37ms.',
                '[info] [phantom] Step anonymous 2/3 https://www.google.co.uk/?gfe_rd=cr&ei=1_awVZm1Nc2q8we32aj4CA (HTTP 200)',
                '<!DOCTYPE html><html itemscope="" itemtype="http://schema.org/WebPage" lang="en-GB"><head><meta content="/images/google_favicon_128.png" itemprop="image"><title>Google</title>',
                '[info] [phantom] Step anonymous 2/3: done in 1275ms.',
                '[info] [phantom] Step anonymous 3/3 https://www.google.co.uk/?gfe_rd=cr&ei=1_awVZm1Nc2q8we32aj4CA (HTTP 200)',
                '[info] [phantom] Step anonymous 3/3: done in 1277ms.',
                '[info] [phantom] Done 3 steps in 1295ms',
            ]));
        $output = $driver->start('https://google.com')
            ->run();

        $this->assertEquals(200, $output->getStatusCode());
    }

    public function testDriverShouldUseProxy()
    {
        $driver = $this->getMockBuilder('CasperJs\Driver\CasperJsDriver')
            ->setMethods(['addOption'])
            ->getMock();
        $driver->expects($this->atLeastOnce())
            ->method('addOption');

        $output = $driver->start('file://' . __DIR__ . '/fixtures/simpleHtml.html')
                         ->useProxy('1.1.1.1')
                         ->run();
    }

    public function testUserAgentIsPresentInScript()
    {
        $expected = <<<FRAGRENT

var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug'
});

casper.userAgent('AmericanPizzaiolo');
casper.then(function() {
    casper.evaluate(function() {
        make me a pizza
    });
});
casper.then(function () {
    this.viewport(1024, 768);
});
casper.waitForSelector(
    '.selector',
    function () {
        this.echo('found selector ".selector"');
    },
    function () {
        this.echo('timeout occured');
    },
    30000
);
casper.wait(
    10000,
    function () {
        this.echo('timeout occured');
    }
);
FRAGRENT;

        $driver = new CasperJsDriver();
        $driver->setUserAgent('AmericanPizzaiolo')
            ->evaluate('make me a pizza')
            ->setViewPort(1024, 768)
            ->waitForSelector('.selector', 30000)
            ->wait(10000);
        $this->assertEquals($expected, $driver->getScript());
    }

    public function testAddScript()
    {
        $driver = new CasperJsDriver();
        $driver->evaluate('make me a pizza');
        $this->assertContains('make me a pizza', $driver->getScript());
    }


}
