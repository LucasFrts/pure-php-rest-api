<?php

namespace Tests\Support;

use App\Support\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
    }

    public function test_default_status_is_200(): void
    {
        $this->assertSame(200, $this->response->getStatusForTest());
    }

    public function test_with_status_returns_same_instance(): void
    {
        $result = $this->response->withStatus(201);
        $this->assertSame($this->response, $result);
    }

    public function test_with_status_sets_status(): void
    {
        $this->response->withStatus(404);
        $this->assertSame(404, $this->response->getStatusForTest());
    }

    public function test_success_sets_200(): void
    {
        $this->response->withStatus(500)->success();
        $this->assertSame(200, $this->response->getStatusForTest());
    }

    public function test_created_sets_201(): void
    {
        $this->response->created();
        $this->assertSame(201, $this->response->getStatusForTest());
    }

    public function test_no_content_sets_204(): void
    {
        $this->response->noContent();
        $this->assertSame(204, $this->response->getStatusForTest());
    }

    public function test_bad_request_sets_400(): void
    {
        $this->response->badRequest();
        $this->assertSame(400, $this->response->getStatusForTest());
    }

    public function test_unauthorized_sets_401(): void
    {
        $this->response->unauthorized();
        $this->assertSame(401, $this->response->getStatusForTest());
    }

    public function test_forbidden_sets_403(): void
    {
        $this->response->forbidden();
        $this->assertSame(403, $this->response->getStatusForTest());
    }

    public function test_not_found_sets_404(): void
    {
        $this->response->notFound();
        $this->assertSame(404, $this->response->getStatusForTest());
    }

    public function test_unprocessable_sets_422(): void
    {
        $this->response->unprocessable();
        $this->assertSame(422, $this->response->getStatusForTest());
    }

    public function test_server_error_sets_500(): void
    {
        $this->response->serverError();
        $this->assertSame(500, $this->response->getStatusForTest());
    }

    public function test_json_sets_data_and_returns_same_instance(): void
    {
        $data = ['key' => 'value'];
        $result = $this->response->json($data);
        $this->assertSame($this->response, $result);
        $this->assertSame($data, $this->response->getDataForTest());
    }

    public function test_with_header_sets_header(): void
    {
        $this->response->withHeader('X-Custom', 'abc');
        $this->assertSame('abc', $this->response->getHeadersForTest()['X-Custom']);
    }

    public function test_default_content_type_is_json(): void
    {
        $this->assertSame('application/json', $this->response->getHeadersForTest()['Content-Type']);
    }

    public function test_chain_fluent_builder(): void
    {
        // Fluent builder puro: withStatus + json + withHeader sem shorthand
        $result = $this->response->withStatus(201)->json(['id' => 1])->withHeader('X-Foo', 'bar');

        $this->assertSame(201, $result->getStatusForTest());
        $this->assertSame(['id' => 1], $result->getDataForTest());
        $this->assertSame('bar', $result->getHeadersForTest()['X-Foo']);
    }

    public function test_shorthand_configura_status_e_dados(): void
    {
        $this->response->created(['id' => 42]);

        $this->assertSame(201, $this->response->getStatusForTest());
        $this->assertSame(['id' => 42], $this->response->getDataForTest());
    }

    public function test_shorthand_configura_headers_adicionais(): void
    {
        $this->response->created([], ['X-Resource' => '/users/42']);

        $this->assertSame(201, $this->response->getStatusForTest());
        $this->assertSame('/users/42', $this->response->getHeadersForTest()['X-Resource']);
    }

    public function test_shorthand_configura_dados_e_headers_juntos(): void
    {
        $this->response->success(['ok' => true], ['X-Foo' => 'bar']);

        $this->assertSame(200, $this->response->getStatusForTest());
        $this->assertSame(['ok' => true], $this->response->getDataForTest());
        $this->assertSame('bar', $this->response->getHeadersForTest()['X-Foo']);
    }

    public function test_shorthand_sem_dados_preserva_dados_definidos_anteriormente(): void
    {
        $this->response->json(['existing' => true])->created();

        $this->assertSame(['existing' => true], $this->response->getDataForTest());
    }

    public function test_no_content_aceita_apenas_headers(): void
    {
        $this->response->noContent(['X-Custom' => 'value']);

        $this->assertSame(204, $this->response->getStatusForTest());
        $this->assertSame('value', $this->response->getHeadersForTest()['X-Custom']);
    }
}
