<?php

namespace Loco\Tests\Http;

use Loco\Http\ApiClient;

/**
 * 
 * The public converter API has been deprecated.
 * @group deprecated
 * 
 * Skip tests requiring internet connection with --exclude-group live
 * @group live
 * @group converter
 */
class ApiClientConvertTest extends ApiClientTest {
    

    /**
     * Make API /convert call
     */
    private function convert( $sourcefile, $from, $ext = 'json', $format = '', $savefile = true ){
        $sourcefile = __DIR__.'/Fixtures/'.$sourcefile;    
        //$this->assertFileExists( $sourcefile );
        $src = file_get_contents( $sourcefile );
        $params = compact('from','ext','format','src');
        $params['name'] = pathinfo( $sourcefile, PATHINFO_FILENAME );
        $params['locale'] = 'fr-FR';
        $response = $this->getClient()->convert( $params );
        $this->assertInstanceOf( '\Loco\Http\Response\RawResponse', $response );
        // write to target file, e.g. export-symfony.php
        if( $savefile ){
            $response = (string) $response;
            $name = $params['name'];
            $format and $name .= '.'.$format;
            $exportfile = __DIR__.'/Fixtures/export/'.$name.'.'.$ext;
            file_put_contents( $exportfile, $response );
        }
        return $response;
    }
    
    


    /**
     * common method to test import/parse success
     */
    private function checkValidJson( $json, $plurals = false, $namespace = '', $prefix = '' ){
        $arr = json_decode( $json, true );
        $this->assertInternalType( 'array', $arr, 'Bad parse: '.$json );
        if( $namespace ){
            foreach( explode('.',$namespace) as $key ){
                $this->assertArrayHasKey( $key, $arr, $namespace.' not in '.$json );
                $arr = $arr[ $key ];
            }
        }
        $sample_key = $prefix.'sample';
        $example_key = $prefix.'example';
        $examples_key = $prefix.'examples';
        //
        $this->assertArrayHasKey( $sample_key, $arr, 'Bad parse: '.$json );
        $this->assertEquals( 'échantillon', $arr[$sample_key], 'string "sample" not parsed' );
        // test plurals, depending on whether plural aware
        $this->assertArrayHasKey( $example_key, $arr, 'Bad parse: '.$json );
        if( $plurals ){
            $pluralarr = $arr[$example_key];
            $this->assertInternalType( 'array', $pluralarr, 'example plural not array' );
            $this->assertArrayHasKey( 'one', $pluralarr );
            $this->assertArrayHasKey( 'other', $pluralarr );
            $this->assertEquals( 'exemple', $pluralarr['one'] );
            $this->assertEquals( 'exemples', $pluralarr['other'] );
        }
        else {
            $this->assertArrayHasKey( $examples_key, $arr, 'Bad parse: '.$json );
            $this->assertEquals( 'exemples', $arr[$examples_key], 'string "examples" not parsed' );
        }
        return $arr;
    }
    
    
    /**
     * common method to check any valid XML
     * @return \DOMDocument
     */
    private function checkValidXml( $xml ){
        $this->assertStringStartsWith( '<?xml ', $xml );
        // attempt basic parse
        $Doc = new \DOMDocument( '1.0', 'UTF-8' );
        $Doc->loadXML( $xml ); 
        $this->assertInstanceOf( 'DOMElement', $Doc->documentElement, 'Bad XML document' );
        return $Doc;
    }
    
    
    /**
     * common method to check any valid YAML
     * @return array
     */
    private function checkValidYaml( $yml ){
        $arr = yaml_parse( $yml );
        $this->assertInternalType( 'array', $arr, 'Bad Yaml' );
        return $arr;
    }
    
    
    
    // Exporters
    // ---------
    
    
    
    /**
     * Export MO from sample seed file
     */
    public function testExportMo(){
        $mo = $this->convert( 'test-fr_FR.po', 'po', 'mo' );
        $this->assertStringStartsWith( pack('V',0x950412de), $mo, 'MO does not start with magic number' );
        $this->assertContains( "sample", $mo );
        $this->assertContains( "specific\x04something", $mo );
        return 'export/test-fr_FR.mo';
    }    
    
    
    /**
     * Export Android XML from sample seed file
     */
    public function testExportAndroid(){
        $xml = $this->convert( 'test-fr_FR.po', 'po', 'xml' );                
        $this->checkValidXml( $xml );
        $this->assertContains( '<string name="sample">échantillon</string>', $xml );
        $this->assertContains( '<item quantity="other">exemples</item>', $xml );
        return 'export/test-fr_FR.xml';
    } 

    
    /**
     * Export TMX from seed file
     */
    public function testExportTMX(){
        $xml = $this->convert( 'test-fr_FR.po', 'po', 'tmx' );                
        $this->checkValidXml( $xml );
        $this->assertContains('<tu tuid="sample">', $xml );
        $this->assertContains('<tu tuid="examples">', $xml );
        $this->assertContains('échantillon', $xml );
        return 'export/test-fr_FR.tmx';
    }

    
    /**
     * Export XLIFF from seed file
     */
    public function testExportXLF(){
        $xml = $this->convert( 'test-fr_FR.po', 'po', 'xlf' );                
        $this->checkValidXml( $xml );
        $this->assertContains('<source>sample</source>', $xml );
        $this->assertContains('<source>examples</source>', $xml );
        $this->assertContains('<target>échantillon</target>', $xml );
        return 'export/test-fr_FR.xlf';
    }


    /**
     * Export ResX from seed file
     */
    public function testExportResX(){
        $xml = $this->convert( 'test-fr_FR.po', 'po', 'resx' );                
        $this->checkValidXml( $xml );
        $this->assertContains('<data name="sample" ', $xml );
        $this->assertContains('<data name="examples" ', $xml );
        $this->assertContains('<value>échantillon</value>', $xml );
        return 'export/test-fr_FR.resx';
    }


    /**
     * Export Java XML properties from seed file
     */
    public function testExportJavaXML(){
        $xml = $this->convert( 'test-fr_FR.po', 'po', 'xml', 'java' );                
        $this->checkValidXml( $xml );
        $this->assertContains('<entry key="sample">échantillon</entry>', $xml );
        $this->assertContains('<entry key="examples">exemples</entry>', $xml );
        return 'export/test-fr_FR.java.xml';
    }


    /**
     * Export XML TS format from seed file
     */
    public function testExportTs(){
        $xml = $this->convert( 'test-fr_FR.po', 'po', 'ts' );                
        $this->checkValidXml( $xml );
        $this->assertContains('<source>sample</source>', $xml );
        $this->assertContains('<translation>échantillon</translation>', $xml );
        // TS supports context groupings
        $this->assertContains('<name>specific</name>', $xml );
        return 'export/test-fr_FR.ts';
    }


    /**
     * Export XML Tizen format from seed file
     */
    public function testExportTizen(){
        $xml = $this->convert( 'test-fr_FR.po', 'po', 'xml', 'tizen' );
        $this->checkValidXml( $xml );
        $this->assertContains('<text id="sample">échantillon</text>', $xml );
        $this->assertContains('<text id="examples">exemples</text>', $xml );
        return 'export/test-fr_FR.tizen.xml';
    }


    /**
     * Export simple Yaml from seed file
     */
    public function testExportSimpleYaml(){
        $yml = $this->convert( 'test-fr_FR.po', 'po', 'yml');
        $arr = $this->checkValidYaml( $yml );
        $this->assertContains('sample: échantillon', $yml );
        $this->assertContains('examples: exemples', $yml );
        // simple yaml shouldn't expand nested key, but message context should be added
        $this->assertContains('specific.something: quelque chose de spécifique', $yml );
        return 'export/test-fr_FR.yml';
    }


    /**
     * Export nested Yaml from seed file
     * @group yaml
     */
    public function testExportNestedYaml(){
        $yml = $this->convert( 'test-fr_FR.po', 'po', 'yml', 'nested' );
        $arr = $this->checkValidYaml( $yml );
        $this->assertContains("fr-FR:\n  test:\n    sample: échantillon", $yml );
        return 'export/test-fr_FR.nested.yml';
    }


    /**
     * Export RoR style Yaml from seed file
     */
    public function testExportRailsYaml(){
        $yml = $this->convert( 'test-fr_FR.po', 'po', 'yml', 'rails' );
        $arr = $this->checkValidYaml( $yml );
        // Rails has short locale and no interim namespace
        $this->assertContains("fr-FR:\n  sample: échantillon", $yml );
        return 'export/test-fr_FR.rails.yml';
    }
    
    
    /**
     * Export CSV file from seed file
     */
    public function testExportCSV(){
        $csv = $this->convert('test-fr_FR.po', 'po', 'csv' );
        // converter renders source language to all multiple locale templates
        $this->assertContains( '"sample","sample","échantillon"', $csv );
        $this->assertContains( '"something-with-commas","something, with, commas","and ""quotes"" too"', $csv );
        return 'export/test-fr_FR.csv';
    }
    
    
    /**
     * Export SQL from seed file
     */
    public function testExportSQL(){
        $sql = $this->convert('test-fr_FR.po', 'po', 'sql' );
        // converter renders source language to all multiple locale templates
        $this->assertContains( "('sample','sample','échantillon')", $sql );
        $this->assertContains( "('something-with-commas','something, with, commas','and \\\"quotes\\\" too')", $sql );
        return 'export/test-fr_FR.sql';
    }
    
    
    /**
     * Export JSON from seed file
     */
    public function testExportJson(){
        $json = $this->convert('test-fr_FR.po', 'po', 'json' );
        $data = $this->checkValidJson( $json, true );
        return 'export/test-fr_FR.json';
    }
    
    
    /**
     * Export Chrome formatted JSON from seed file
     * @group chrome
     */
    public function testExportJsonChrome(){
        $json = $this->convert('test-fr_FR.po', 'po', 'json', 'chrome' );
        $data = json_decode( $json, true );
        $this->assertEquals( array(
            'message' => 'échantillon',
            'description' => 'Sample notes',
        ), $data['sample'] );
        return 'export/test-fr_FR.chrome.json';
    }

    
   /**
    * Export JavaScript function from seed
    */
    public function testExportJS(){
        $js = $this->convert('test-fr_FR.po', 'po', 'js' );
        $this->assertContains('["one","other"]', $js );
        return 'export/test-fr_FR.js';
    }    

    
   /**
    * Export JavaScript Gettest.js from seed
    */
    public function testExportJSGettext(){
        $js = $this->convert('test-fr_FR.po', 'po', 'js', 'gettext' );
        $this->assertContains('{"lang":"French (France)","plural-forms":"nplurals=2;', $js );
        $this->assertContains('["examples","exemple","exemples"]', $js );
        return 'export/test-fr_FR.gettext.js';
    }    
    
    
    /**
     * Export PHP default (Zend) from seed
     * @group php
     */    
    public function testExportZend(){
        $php = $this->convert('test-fr_FR.po', 'po', 'php' );
        $this->assertStringStartsWith('<?php', $php );
        $this->assertContains("'sample' => 'échantillon'", $php );
        $this->assertContains("1 => 'exemples'", $php );
        return 'export/test-fr_FR.php';
    }    
    
    
    /**
     * Export PHP Symfony format from seed
     * @group php
     */    
    public function testExportSymfony(){
        $php = $this->convert('test-fr_FR.po', 'po', 'php', 'symfony' );
        $this->assertStringStartsWith('<?php', $php );
        $this->assertContains("'sample' => 'échantillon'", $php );
        $this->assertContains("'example' => 'exemple|exemples'", $php );
        return 'export/test-fr_FR.symfony.php';
    }    
    
    
    /**
     * Export PHP Code Igniter format from seed
     * @group php
     */    
    public function testExportCodeIgniter(){
        $php = $this->convert('test-fr_FR.po', 'po', 'php', 'codeigniter' );
        $this->assertStringStartsWith('<?php', $php );
        $this->assertContains("\$lang['test_sample'] = 'échantillon';", $php );
        $this->assertContains("\$lang['test_specific']['something'] = 'quelque chose de spécifique';", $php, 'Code igniter should fold on dot syntax' );
        return 'export/test-fr_FR.codeigniter.php';
    }    

    
    /**
     * Export PHP Constants from seed
     */    
    public function testExportPHPConstants(){
        $php = $this->convert('test-fr_FR.po', 'po', 'php', 'constants' );
        $this->assertStringStartsWith('<?php', $php );
        $this->assertContains("define('TEST_EXAMPLES', 'exemples');", $php );
        return 'export/test-fr_FR.constants.php';
    }    
    
    
    
    /**
     * Export iOS strings from seed
     * @group ios
     */
    public function testExportLocalizableStrings(){
        $src = $this->convert('test-fr_FR.po', 'po', 'strings' );
        // check is UTF-16
        $this->assertStringStartsWith( "\xFE\xFF", $src );
        $this->assertContains( iconv( 'UTF-8', 'UTF-16BE', '"examples" = "exemples";' ), $src );
        return 'export/test-fr_FR.strings';
    }    
    
    
    /**
     * Export Java .properties from seed    
     * @group java
     */
    public function testExportJavaProprties(){
        $src = $this->convert( 'test-fr_FR.po', 'po', 'properties' );
        $this->assertStringStartsWith('# Loco', $src );
        $this->assertContains('sample = \u00E9chantillon', $src );
        return 'export/test-fr_FR.properties';    
    }    
    
    
    /**
     * Export HTML table from seed    
     */
    public function testExportHTMLTable(){
        $src = $this->convert( 'test-fr_FR.po', 'po', 'html' );
        $this->assertStringStartsWith('<!DOCTYPE html>', $src );
        $this->assertContains('<td><code>specific.something</code></td>', $src );
        $this->assertContains('<td class="fr">and &quot;quotes&quot; too</td>', $src );
        return 'export/test-fr_FR.html';    
    }    
    
    
    /**
     * Export symfony-style INI file from seed    
     * @group ini
     */
    public function testExportINI(){
        $src = $this->convert( 'test-fr_FR.po', 'po', 'ini' );
        $this->assertStringStartsWith(';; ', $src );
        $this->assertContains('sample = "échantillon"', $src );
        $this->assertContains("= \"c'est \nmulti\"", $src );
        return 'export/test-fr_FR.ini';    
    }    

    
        
    
    // Importers
    // ---------
    
    
    
    /**
     * Test MO parser
     * @depends testExportMo
     */
    public function testImportMO( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'mo'), true );
    }
    
    
    /**
     * Test Android XML Parser
     * @depends testExportAndroid
     */
    public function testImportAndroid( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'xml', 'json', '', false ), true );
    }


    /**
     * Test TMX Parser
     * @depends testExportTMX
     */
    public function testImportTMX( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'tmx', 'json', '', false ) );
    }


    /**
     * Test XLF Parser
     * @depends testExportXLF
     */
    public function testImportXLF( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'xlf', 'json', '', false ) );
    }


    /**
     * Test Resx Parser
     * @depends testExportResX
     */
    public function testImportResX( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'resx', 'json', '', false ) );
    }


    /**
     * Test Java XML Parser
     * @group xml
     * @group java
     * @depends testExportJavaXML
     */
    public function testImportJavaXML( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'xml', 'json', '', false ) );
    }


    /**
     * Test TS XML Parser
     * @depends testExportTs
     */
    public function testImportTs( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'ts', 'json', '', false ), true, 'test' );
    }


    /**
     * Test Tizen XML Parser
     * @depends testExportTizen
     */
    public function testImportTizen( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'xml', 'json', '', false ) );
    }


    /**
     * Test simple yaml Parser
     * @group yaml
     * @depends testExportSimpleYaml
     */
    public function testImportSimpleYaml( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'yml', 'json', '', false ) );
    }


    /**
     * Test nested yaml Parser
     * @group yaml
     * @depends testExportNestedYaml
     */
    public function testImportNestedYaml( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'yml', 'json', '', false ), true, 'test' );
    }
    
    
    /**
     * Test json parse
     * @depends testExportJson
     */
    public function testImportJson( $sourcefile ){
        $json = $this->convert( $sourcefile, 'json', 'json', '', false );
        $this->checkValidJson( $json, true );
    }
    
    
    /**
     * Test php parse from zend format
     * @depends testExportZend
     */
    public function testImportZend( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'php', 'json', '', false ), true );
    }
    
    
    /**
     * Test php parse from symfony format
     * @group php
     * @depends testExportSymfony
     */
    public function testImportSymfony( $sourcefile ){
        $json = $this->convert( $sourcefile, 'php', 'json', '', false );
        // re-importing symfony plurals won't work due to the way symfony handles string formatted plurals
        $data = json_decode( $json, true );
        $this->assertEquals('exemple|exemples', $data['example'] );
    }
    
    
    /**
     * Test php parse from CodeIgniter format
     * @group php
     * @depends testExportCodeIgniter
     */
    public function testImportCodeIgniter( $sourcefile ){
        //$this->markTestSkipped(); // until CI import matches export (dot syntax)
        $this->checkValidJson( $this->convert( $sourcefile, 'php', 'json', '', false ), false, '', 'test_' );
    }
            
    
    /**
     * Test .properties parser
     * @group java
     * @depends testExportJavaProprties
     */            
    public function testImportJavaProperties( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'properties', 'json', '', false ), false );
    }
    
    
    /**
     * Test .strings parser
     * @group ios
     * @depends testExportLocalizableStrings
     */    
    public function testImportLocalizableStrings( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'strings', 'json', '', false ), false );
    }
    
    
    /**
     * Test .ini parser
     * @group ini
     * @depends testExportINI
     */
    public function testImportINI( $sourcefile ){
        $this->checkValidJson( $this->convert( $sourcefile, 'ini', 'json', '', false ), false );
    }            
            
    
}