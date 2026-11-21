<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginCorsPreflightTest extends TestCase
{
    public function test_login_preflight_allows_local_frontend_origins(): void
    {
        $origins = ['http://localhost:5174', 'http://localhost:4173'];
        config(['cors.allowed_origins' => $origins]);

        foreach ($origins as $origin) {
            $response = $this->call('OPTIONS', '/api/auth/login', [], [], [], [
                'HTTP_ORIGIN' => $origin,
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
            ]);

            $response->assertStatus(204);
            $response->assertHeader('Access-Control-Allow-Origin', $origin);
            $this->assertStringContainsString(
                'POST',
                $response->headers->get('Access-Control-Allow-Methods', ''),
            );
        }
    }

    public function test_extension_preflight_allows_chromium_and_firefox_origins(): void
    {
        $origins = [
            'chrome-extension://abcdefghijklmnopabcdefghijklmnop',
            'moz-extension://test-extension-id',
        ];

        foreach ($origins as $origin) {
            $response = $this->call('OPTIONS', '/api/extension/session', [], [], [], [
                'HTTP_ORIGIN' => $origin,
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization',
            ]);

            $response->assertStatus(204);
            $response->assertHeader('Access-Control-Allow-Origin', $origin);
            $this->assertStringContainsString(
                'GET',
                $response->headers->get('Access-Control-Allow-Methods', ''),
            );
        }
    }
}
