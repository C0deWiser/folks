<?php

namespace Codewiser\Folks\Http\Resources;

use Codewiser\Folks\Contracts\UserProviderContract;
use Codewiser\Folks\Folks;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $schema = app()->call(function (UserProviderContract $userProvider) {
            return $userProvider->schema();
        });

        $data = parent::toArray($request);
        $response = [];

        foreach ($schema as $control) {
            if (in_array((string)$control, array_keys($data))) {
                $response[(string)$control] = $control($data[(string)$control]);
            }
        }

        return $response;
    }
}
