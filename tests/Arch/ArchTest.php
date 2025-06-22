<?php

arch()
    ->expect('Sapphire\Mapper')
    ->not->toUse(['die', 'dd', 'dump', 'var_dump', 'print_r', 'var_export', 'print', 'exit', 'echo']);

arch()
    ->expect('Sapphire\Mapper\Exception')
    ->toBeClasses()
    ->toExtend('RuntimeException');

arch()
    ->expect('Sapphire\Mapper')
    ->toBeClasses()
    ->ignoring([
        'Sapphire\Mapper\DynamoMapperInterface',
        'Sapphire\Mapper\DynamoType',
        'Sapphire\Mapper\Caster\CasterInterface',
    ]);

arch()
    ->expect('Sapphire\Mapper\DynamoMapper')
    ->toImplement('Sapphire\Mapper\DynamoMapperInterface');

arch()->preset()->php();
