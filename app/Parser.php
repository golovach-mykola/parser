<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Parser extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'site', 'last_parse'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_parse' => 'datetime',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;
}
