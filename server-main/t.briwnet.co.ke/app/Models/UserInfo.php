<?php

namespace App\Models;

use App\CentralLogics\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

class UserInfo extends Model
{
    use HasFactory;

    protected $casts = [
        'user_id' => 'integer',
        'vendor_id' => 'integer',
        'deliveryman_id' => 'integer',
        'admin_id' => 'integer'
    ];
    protected $appends = ['image_full_url'];
    public function getImageFullUrlAttribute(){
        $value = $this->image;
        $path = 'profile';
        if ($this->user_id){
            $path = 'profile';
            $storages = $this->user?->storage;
        }elseif ($this->vendor_id){
            $path = 'store';
            $storages = $this->vendor?->storage;
        }elseif ($this->deliveryman_id){
            $path = 'delivery-man';
            $storages = $this->delivery_man?->storage;
        }

        if (count($storages) > 0) {
            foreach ($storages as $storage) {
                if ($storage['key'] == 'image') {
                    if($storage['value'] == 's3'){

                        return Helpers::s3_storage_link($path,$value);
                    }else{
                        return Helpers::local_storage_link($path,$value);
                    }
                }
            }
        }

        return Helpers::local_storage_link($path,$value);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'deliveryman_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function storage()
    {
        return $this->morphMany(Storage::class, 'data');
    }
    protected static function booted()
    {
        static::addGlobalScope('storage', function ($builder) {
            $builder->with('storage');
        });
    }
    protected static function boot()
    {
        parent::boot();
        static::saved(function ($model) {
            if($model->isDirty('image')){
                $value = Helpers::getDisk();

                DB::table('storages')->updateOrInsert([
                    'data_type' => get_class($model),
                    'data_id' => $model->id,
                    'key' => 'image',
                ], [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

    }
}
