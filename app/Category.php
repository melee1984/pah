<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category';
	protected $fillable = array('user_id','name', 'identifier', 'active','parent_category_id');
	public $timestamps = true;

	public function parent()
    {
        return $this->hasOne('App\Category', 'id', 'parent_category_id');
    }

    public function products() {
        return $this->hasMany('App\Products', 'category_id', 'id')->whereActive(1);
    }

}
