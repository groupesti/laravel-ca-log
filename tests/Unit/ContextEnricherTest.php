<?php

declare(strict_types=1);

use CA\Log\Services\ContextEnricher;
use Illuminate\Http\Request;

beforeEach(function (): void {
    config()->set('ca-log.enrichment.enabled', true);
    config()->set('ca-log.enrichment.include_request_ip', true);
    config()->set('ca-log.enrichment.include_user_agent', false);
    config()->set('ca-log.enrichment.include_authenticated_user', false);
});

it('enriches context with timestamp and environment', function (): void {
    $request = Request::create('/test');
    $enricher = new ContextEnricher($request);

    $result = $enricher->enrich(['ca_id' => 'ca-001']);

    expect($result)
        ->toHaveKey('timestamp')
        ->toHaveKey('environment')
        ->toHaveKey('hostname')
        ->toHaveKey('ca_id', 'ca-001');
});

it('includes request IP when configured', function (): void {
    $request = Request::create('/test', 'GET', server: ['REMOTE_ADDR' => '192.168.1.100']);
    $enricher = new ContextEnricher($request);

    $result = $enricher->enrich([]);

    expect($result)->toHaveKey('request_ip');
});

it('excludes user agent when not configured', function (): void {
    $request = Request::create('/test');
    $enricher = new ContextEnricher($request);

    $result = $enricher->enrich([]);

    expect($result)->not->toHaveKey('user_agent');
});

it('includes user agent when configured', function (): void {
    config()->set('ca-log.enrichment.include_user_agent', true);

    $request = Request::create('/test', 'GET', server: ['HTTP_USER_AGENT' => 'TestAgent/1.0']);
    $enricher = new ContextEnricher($request);

    $result = $enricher->enrich([]);

    expect($result)->toHaveKey('user_agent');
});

it('preserves existing context values', function (): void {
    $request = Request::create('/test');
    $enricher = new ContextEnricher($request);

    $result = $enricher->enrich([
        'certificate_serial' => 'ABC123',
        'subject' => 'CN=test',
    ]);

    expect($result)
        ->toHaveKey('certificate_serial', 'ABC123')
        ->toHaveKey('subject', 'CN=test');
});

it('supports persistent context', function (): void {
    $request = Request::create('/test');
    $enricher = new ContextEnricher($request);

    $enricher->setPersistentContext('ca_id', 'root-ca');

    $result1 = $enricher->enrich([]);
    $result2 = $enricher->enrich(['extra' => 'data']);

    expect($result1)->toHaveKey('ca_id', 'root-ca');
    expect($result2)
        ->toHaveKey('ca_id', 'root-ca')
        ->toHaveKey('extra', 'data');
});

it('clears persistent context', function (): void {
    $request = Request::create('/test');
    $enricher = new ContextEnricher($request);

    $enricher->setPersistentContext('ca_id', 'root-ca');
    $enricher->clearPersistentContext();

    $result = $enricher->enrich([]);

    expect($result)->not->toHaveKey('ca_id');
});

it('returns empty enrichment when disabled', function (): void {
    config()->set('ca-log.enrichment.enabled', false);

    $request = Request::create('/test');
    $enricher = new ContextEnricher($request);

    $result = $enricher->enrich(['ca_id' => 'test']);

    expect($result)
        ->toHaveKey('ca_id', 'test')
        ->not->toHaveKey('timestamp')
        ->not->toHaveKey('environment');
});
