<?php
 
namespace App;
 
use Illuminate\Database\Eloquent\Model;
 
class Comment extends Model
{
    protected $table = 'comments';
 
    protected $fillable = ['id', 'screenshot_id', 'x_pos', 'y_pos', 'comment'];
}