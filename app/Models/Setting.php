<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Key/value site settings; read through App\Services\SettingsService. */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];
}
