<?php

declare(strict_types=1);

use BjTheCod3r\Spotify\Tests\Stubs\TestUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects to the Spotify authorize endpoint with PKCE parameters', function (): void {
    $this->actingAs(new TestUser(id: 7));

    $response = $this->withSession([])->get('/spotify/connect');

    $response->assertStatus(302);

    $target = $response->headers->get('Location');
    expect($target)->toBeString();
    expect($target)->toStartWith('https://accounts.spotify.com/authorize?');

    $query = [];
    parse_str(parse_url((string) $target, PHP_URL_QUERY) ?: '', $query);

    expect($query)
        ->toHaveKey('response_type', 'code')
        ->toHaveKey('client_id', 'test-client-id')
        ->toHaveKey('redirect_uri', 'https://app.test/spotify/callback')
        ->toHaveKey('code_challenge_method', 'S256')
        ->toHaveKeys(['state', 'code_challenge', 'scope']);

    expect($query['scope'])->toContain('user-read-private');

    $session = session();
    expect($session->get('spotify.oauth.state'))->toBe($query['state']);
    expect($session->get('spotify.oauth.verifier'))->toBeString()->not->toBeEmpty();
});

it('merges extra scopes with the configured defaults', function (): void {
    $this->actingAs(new TestUser());

    $response = $this->withSession([])->get('/spotify/connect?scopes=playlist-modify-public');

    $target = $response->headers->get('Location');
    $query = [];
    parse_str(parse_url((string) $target, PHP_URL_QUERY) ?: '', $query);

    expect($query['scope'])
        ->toContain('playlist-modify-public')
        ->toContain('user-read-private');
});
