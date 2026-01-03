<?php

test('the application redirects to admin', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin/dashboards');
});

test('the admin dashboards page loads', function () {
    $response = $this->get('/admin/dashboards');

    $response->assertStatus(200);
});
