<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'booking_date' => 'required',
            'slot_id' => 'required',
            'no_of_persons' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'booking_date.required' => 'Booking date is required',
            'slot_id.required' => 'Slot is required',
            'no_of_persons.required' => 'No of persons is required',
        ];
    }
}
