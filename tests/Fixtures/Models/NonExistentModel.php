<?php

namespace AnasNashat\EasyDev\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class NonExistentModel extends Model
{
    protected $table = 'non_existent_table';
    protected $fillable = ['name'];
}
