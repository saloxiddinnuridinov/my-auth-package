<?php

namespace My\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
/**
 *  @OA\Schema(
 *      schema="Error",
 *      title="ResponseError",
 * 	    @OA\Property(property="errors",type="array",
 *           @OA\Items(type="string", format="text",example="Not Found"),
 *      )
 *  )
 */

class ValidatorResponse
{
    public $fails = false;

    public $response;

    public function check(Request $request, $rules)
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $result = [];
            $messages = json_decode(json_encode($validator->messages()), true);
            foreach ($messages as $value) {
                foreach ($value as $item) {
                    $result[] = $item;
                }
            }
            $this->fails = true;
            $this->response = ["errors" => $result];
        }
        else{
            $this->fails = false;
        }
    }
}
