<?php

it('taxonomy schema has proper foreign key reference', function () {
    $schema = file_get_contents(CROSSROADS_CORE_DIR . '/schemas/taxonomy.sql');

    expect($schema)->toContain('REFERENCES content(id)');
});

it('taxonomy schema does not contain FOREIGH typo', function () {
    $schema = file_get_contents(CROSSROADS_CORE_DIR . '/schemas/taxonomy.sql');

    expect($schema)->not->toContain('FOREIGH');
});
