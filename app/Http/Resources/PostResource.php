<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * @var bool
     */
    public $status;

    /**
     * @var string
     */
    public $message;

    /**
     * PostResource constructor.
     *
     * @param $resource
     * @param bool $status
     * @param string $message
     */
    public function __construct($resource, $status = true, $message = 'List Data Post')
    {
        parent::__construct($resource);
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */

     public function toArray($request)
    {
        return [
            'success'   => $this->status,
            'message'   => $this->message,
            'data'      => $this->resource
        ];
    }
}
