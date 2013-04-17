<?php

use Aerys\Parsing\PeclMessageParser;

class PeclMessageParserTest extends PHPUnit_Framework_TestCase {
    
    private function skipIfMissingExtHttp() {
        if (!extension_loaded('http')) {
            $this->markTestSkipped(
                'http extension not available'
            );
        }
    }
    
    /**
     * @dataProvider provideParseExpectations
     */
    public function testParseHeaders($msg, $method, $uri, $protocol, $headers, $body) {
        $this->skipIfMissingExtHttp();
        
        $inputStream = fopen('php://memory', 'r+');
        fwrite($inputStream, $msg);
        rewind($inputStream);
        
        $msgParser = new PeclMessageParser($inputStream);
        $parsedRequestArr = $msgParser->parse();
        
        $this->assertEquals($method, $parsedRequestArr['method']);
        $this->assertEquals($uri, $parsedRequestArr['uri']);
        $this->assertEquals($protocol, $parsedRequestArr['protocol']);
        $this->assertEquals($headers, $parsedRequestArr['headers']);
        $this->assertEquals($body, $parsedRequestArr['body']);
    }
    
    /**
     * @expectedException Aerys\Parsing\HeaderSyntaxException
     */
    public function testParseHeadersThrowsExceptionOnFailure() {
        $this->skipIfMissingExtHttp();
        
        $msg = "GET / HTTP/1.1" . "\r\n" . "Not-Complete\r\n\r\n";
        
        $inputStream = fopen('php://memory', 'r+');
        fwrite($inputStream, $msg);
        rewind($inputStream);
        
        $msgParser = new PeclMessageParser($inputStream);
        $parsedRequestArr = $msgParser->parse();
    }
    
    
    public function provideParseExpectations() {
        $return = [];
        
        // 0 -------------------------------------------------------------------------------------->
        $msg = "" .
            "GET / HTTP/1.1" . "\r\n" . 
            "Host: localhost" . "\r\n" . 
            "\r\n"
        ;
        
        $method = 'GET';
        $uri = '/';
        $protocol = '1.1';
        $headers = ['HOST' => 'localhost'];
        $body = NULL;
        
        $return[] = [$msg, $method, $uri, $protocol, $headers, $body];
        
        // 1 -------------------------------------------------------------------------------------->
        $msg = "" .
            "POST /post-endpoint HTTP/1.0" . "\r\n" . 
            "Host: localhost" . "\r\n" . 
            "Cookie: cookie1" . "\r\n" . 
            "Cookie: cookie2" . "\r\n" . 
            "Content-Length: 3" . "\r\n" .
            "\r\n" .
            "123"
        ;
        
        $method = 'POST';
        $uri = '/post-endpoint';
        $protocol = '1.0';
        $headers = [
            'HOST' => 'localhost',
            'COOKIE' => ['cookie1', 'cookie2'],
            'CONTENT-LENGTH' => 3
        ];
        $body = '123';
        
        $return[] = [$msg, $method, $uri, $protocol, $headers, $body];
        
        // 2 -------------------------------------------------------------------------------------->
        $msg = "" .
            "OPTIONS * HTTP/1.0" . "\r\n" . 
            "\r\n"
        ;
        
        $method = 'OPTIONS';
        $uri = '*';
        $protocol = '1.0';
        $headers = [];
        $body = NULL;
        
        $return[] = [$msg, $method, $uri, $protocol, $headers, $body];
        
        // 3 -------------------------------------------------------------------------------------->
        $msg = "" .
            "GET /test HTTP/1.1\r\n" .
            "Host: localhost\r\n" .
            "Transfer-Encoding: chunked\r\n" .
            "\r\n" .
            "00000\r\n\r\n"
        ;
        
        $method = 'GET';
        $uri = '/test';
        $protocol = '1.1';
        $headers = [
            'HOST' => 'localhost',
            'TRANSFER-ENCODING' => 'chunked'
        ];
        $body = NULL;
        
        $return[] = [$msg, $method, $uri, $protocol, $headers, $body];
        
        // 4 -------------------------------------------------------------------------------------->
        
        $len = 1992;
        $body = str_repeat('x', $len);
        
        $msg = '' .
            "GET /test HTTP/1.1\r\n" .
            "Host: localhost\r\n" .
            "Connection: keep-alive\r\n" .
            "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11\r\n" .
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
            "Accept-Encoding: gzip,deflate,sdch\r\n" .
            "Accept-Language: en-US,en;q=0.8\r\n" .
            "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3\r\n" .
            "Content-Length: {$len}\r\n" .
            "\r\n" .
            "{$body}"
        ;
        
        $method = 'GET';
        $uri = '/test';
        $protocol = '1.1';
        $headers = [
            'HOST' => 'localhost',
            'CONNECTION' => 'keep-alive',
            'USER-AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11',
            'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'ACCEPT-ENCODING' => 'gzip,deflate,sdch',
            'ACCEPT-LANGUAGE' => 'en-US,en;q=0.8',
            'ACCEPT-CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'CONTENT-LENGTH' => $len
        ];
        
        $return[] = [$msg, $method, $uri, $protocol, $headers, $body];
        
        // 5 -------------------------------------------------------------------------------------->
        $msg = "" .
            "GET /test HTTP/1.1\r\n" .
            "Host: localhost\r\n" .
            "Header-Lws-Split: line1\r\n" .
            "\x20line2\r\n" .
            "\x20line3\n" .
            "\x20line4\r\n" .
            "\tline5\r\n" .
            "\r\n"
        ;
        
        $method = 'GET';
        $uri = '/test';
        $protocol = '1.1';
        $headers = [
            'HOST' => 'localhost',
            'HEADER-LWS-SPLIT' => "line1\r\n" .
                "\x20line2\r\n" .
                "\x20line3\n" .
                "\x20line4\r\n" .
                "\tline5"
        ];
        $body = NULL;
        
        $return[] = [$msg, $method, $uri, $protocol, $headers, $body];
        
        // x -------------------------------------------------------------------------------------->
        
        return $return;
    }
    
}
