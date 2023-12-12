<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IotCatBox extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'iot_cat_box';
    protected $fillable = [
        'name', 'location', 'iot_mac_id'
    ];
}
