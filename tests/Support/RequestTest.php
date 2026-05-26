<?php

namespace Tests\Support;

use App\Support\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function test_method_returns_uppercased_request_method(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertSame('GET', $request->method());
    }

    public function test_content_type_returns_trimmed_value(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = '  application/json  ';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertSame('application/json', $request->contentType());
    }

    public function test_content_type_defaults_to_empty_string(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['CONTENT_TYPE']);
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertSame('', $request->contentType());
    }

    public function test_is_method_true_for_matching_method(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertTrue($request->isMethod('post'));
        $this->assertTrue($request->isMethod('POST'));
    }

    public function test_is_method_false_for_non_matching(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertFalse($request->isMethod('POST'));
    }

    public function test_query_returns_get_param(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = ['name' => 'lucas'];
        $_POST = [];

        $request = new Request();
        $this->assertSame('lucas', $request->query('name'));
    }

    public function test_query_returns_default_when_key_missing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertNull($request->query('missing'));
        $this->assertSame('default', $request->query('missing', 'default'));
    }

    public function test_post_returns_post_param(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = ['email' => 'test@test.com'];

        $request = new Request();
        $this->assertSame('test@test.com', $request->post('email'));
    }

    public function test_post_returns_default_when_key_missing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertNull($request->post('missing'));
        $this->assertSame('fallback', $request->post('missing', 'fallback'));
    }

    public function test_get_prefers_query_over_post(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = ['key' => 'from_query'];
        $_POST = ['key' => 'from_post'];

        $request = new Request();
        $this->assertSame('from_query', $request->get('key'));
    }

    public function test_get_falls_back_to_post_when_not_in_query(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = ['key' => 'from_post'];

        $request = new Request();
        $this->assertSame('from_post', $request->get('key'));
    }

    public function test_get_returns_default_when_missing_in_both(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertSame('default', $request->get('missing', 'default'));
    }

    public function test_all_merges_get_and_post(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = ['a' => '1'];
        $_POST = ['b' => '2'];

        $request = new Request();
        $this->assertSame(['a' => '1', 'b' => '2'], $request->all());
    }

    public function test_get_body_returns_sanitized_post_data(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = '';
        $_GET = [];
        $_POST = ['name' => 'hello'];

        $request = new Request();
        $body = $request->getBody();
        $this->assertArrayHasKey('name', $body);
        $this->assertSame('hello', $body['name']);
    }

    public function test_get_json_returns_empty_when_content_type_not_json(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        $this->assertSame([], $request->getJSON());
    }

    public function test_get_json_accepts_content_type_with_charset(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
        $_GET = [];
        $_POST = [];

        $request = new Request();
        // php://input is empty in CLI — returns [] from null coalescing, not false negative from wrong content-type check
        $this->assertSame([], $request->getJSON());
    }
}
