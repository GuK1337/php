<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'folder_id',
        'users',
        'path',
        'author'
    ];
    protected $hidden= ['created_at', 'updated_at'];
}
