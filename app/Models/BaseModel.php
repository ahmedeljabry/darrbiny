<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseModel extends Model
{
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::updating(function (Model $model) {
            if ($model->isDirty()) {
                if ($model->getAttribute('version') !== null) {
                    $model->setAttribute('version', (int) $model->getAttribute('version') + 1);
                }
            }
        });
    }
}

