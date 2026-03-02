<?php

it('taxonomy schema has correct structure and references', function () {
    $schemaPath = CROSSROADS_CORE_DIR . '/schemas/taxonomy.sql';
    expect(file_exists($schemaPath))->toBeTrue();

    $schema = file_get_contents($schemaPath);

    expect($schema)->toContain('REFERENCES content(id)')
        ->and($schema)->not->toContain('FOREIGH');
});
