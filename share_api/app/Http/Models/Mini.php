<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Mini extends Model
{
    protected  $table = 'miniuser';
    protected $fillable = ['openid', 'nickname', 'avatarurl', 'gender', 'city', 'province', 'country', 'code', 'parent_key']; //批量赋值

    public function lists()
    {
        return $this->get();
    }

    public function findByOpenid($openid)
    {
        return $this->where('openid', $openid)->first();
    }

    public function findParentByOpenid($openid)
    {
        $res = null;
        $self = $this->where('openid', $openid)->first();
        if($self){
            $parent = $this->where('code', $self['parent_key'])->first();
            if($parent){
                $res = $parent;
            }
        }

        return $res;
    }

    public function findChildrenByCode($code)
    {
        return $this->where('parent_key', $code)->get();
    }
}
