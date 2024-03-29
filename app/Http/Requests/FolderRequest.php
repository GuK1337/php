<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class FolderRequest extends FormRequest
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
    public function rules(Request $request)
    {
        $rules = [
            'name'=> 'required|string',
            'parent_id'=> 'string',
            'users'=> 'array'
        ];

        switch ($this->getMethod()){
            case 'PUT':
            case 'POST':
                return $rules;
        }


    }


}
