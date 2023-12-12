<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedData extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'feed_data';
    protected $fillable = [
      'wix_user_id', 'iot_cat_box_id', 'point'
    ];
}
