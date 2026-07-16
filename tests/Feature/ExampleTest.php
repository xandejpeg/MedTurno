<?php

it('redirects guests from the root to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

it('blocks guests from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('does not expose a public registration route', function () {
    $this->get('/register')->assertNotFound();
});
