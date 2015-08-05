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

    public function testDriverWillProperlyBuildInteractionScript()
    {
        $expected = "
var casper = require('casper').create({
  verbose: true,
  logLevel: 'debug',
  colorizerType: 'Dummy'
});

casper.userAgent('AmericanPizzaiolo');
casper.page.customHeaders = {
    'Accept-Language': 'en-US'
};
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
        this.echo('found selector \".selector\"');
    },
    function () {
        this.echo('" . Output::TAG_TIMEOUT . "');
    },
    30000
);
casper.wait(
    10000,
    function () {
        this.echo('" . Output::TAG_TIMEOUT . "');
    }
);";

        $driver = new CasperJsDriver();
        $driver->setUserAgent('AmericanPizzaiolo')
               ->setAcceptLanguage(['en-US'])
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

    /**
     * @expectedException \Exception
     */
    public function testCrawlerShouldThrowExceptionWhenTimingOut()
    {
        $driver = new CasperJsDriver();

        $output = $driver->start('file://' . __DIR__ . '/fixtures/simpleHtml.html')
            ->waitForSelector('.some-non-existent-selector', 100)
            ->run();
    }
}
