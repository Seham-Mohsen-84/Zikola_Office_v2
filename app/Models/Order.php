<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
    protected $fillable = [
        'order_notes',
        'status',
        'user_id',
        'item_id',
        'number_of_sugar_spoons',
        'voice'
    ];

    protected $appends = ['voice_url'];

    public function getVoiceUrlAttribute(){
        if (!$this->voice){
            return null;
        }
        return asset(Storage::url($this->voice));
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function item(){
        return $this->belongsTo(Item::class);
    }
}
