<?php

test('it shows an error if schema file does not exist', function () {
    $this->artisan('kaleido:sync')
        ->expectsOutputToContain('Schema file not found')
        ->assertFailed();
});
