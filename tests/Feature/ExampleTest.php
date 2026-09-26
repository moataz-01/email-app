<?php

test('launches email setup on a fresh installation', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('accounts.create'));
});
