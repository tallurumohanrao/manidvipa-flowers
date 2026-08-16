<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $guard = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['profile_id','name', 'dob', 'height', 'weight', 'marital_status', 'body_type', 'physical_status', 'mother_tongue', 'eating_habits', 'drinking_habits', 'smoking_habits', 'religion', 'caste', 'sub_caste', 'gothram', 'raasi', 'star', 'padam', 'dosham', 'time_of_birth', 'country', 'state', 'city', 'education_details', 'citizenship', 'living_country', 'living_state', 'living_city', 'employed_in', 'occupation', 'organization', 'annual_income', 'family_value', 'family_type', 'family_status', 'father_occupation', 'mother_occupation', 'brothers', 'married_brothers', 'sisters', 'married_sisters', 'family_living_country', 'family_living_state', 'family_living_city', 'hobbies_interests'];

    public static function boot()
    {
        parent::boot();

        static::created(function($profile) {
            $profile->profile_id .= 'AOM' . str_pad($profile->id, 4, '0', STR_PAD_LEFT);
            $profile->save();
        });

        // static::created(function($profile) {
        //     $profile->profile_id .= 'AOM' . $profile->id;
        //     $profile->save();
        // });
    }

    // public function setProfileIdAttribute(){
    //     $max = $this::max('id') + 1;
    //     return $this->attributes['profile_id'] = 'AOM'. str_pad($max, 3, '0', STR_PAD_LEFT);
    // }

    // public function setProfileIdAttribute(){
    //     $max = $this::max('id') + 1;
    //     return $this->attributes['profile_id'] = 'AOM'. str_pad($max, 3, '0', STR_PAD_LEFT);
    // }
}
