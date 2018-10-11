<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Tree extends Model
{
    protected  $table = 'tree';
    protected $fillable = ['path', 'label', 'level', 'is_root', 'sort', 'is_open', 'description', 'content', 'img_url']; //批量赋值

    public function lists()
    {
        return $this->whereNull('deleted_at')
            ->where('is_open', 1)
            ->orderBy('sort', 'asc')
            ->get();
    }

    public function insert($params)
    {
        return $this->insertGetId($params);
    }

    public function rowById($id){
        return $this->select('*')->where('id', $id)->first();
    }

    public function column($level)
    {
        return $this->whereNull('deleted_at')
            ->where('level', $level)
            ->where('is_open', 1)
            ->orderBy('sort', 'asc')
            ->get();
    }

    public function children($path)
    {
        $key = $path.'/%';
        return $this->whereNull('deleted_at')
            ->where('is_open', 1)
            ->where('path','like', $key)
            ->orderBy('sort', 'asc')
            ->get();
    }
}
