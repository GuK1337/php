<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent',
        'users',
    ];
    protected $hidden= ['created_at', 'updated_at'];
}
