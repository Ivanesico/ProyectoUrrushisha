<?php

namespace Tests;

use App\Models\TobaccoType;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Spec 012: id del tipo de tabaco a partir de su valor antiguo ("sin_nicotina" → "Sin nicotina").
     * Los 6 tipos los crea la migración en cada base de datos de los tests.
     */
    protected function tobaccoType(string $oldValue): int
    {
        return TobaccoType::where('name', ucfirst(str_replace('_', ' ', $oldValue)))->valueOrFail('id');
    }
}
