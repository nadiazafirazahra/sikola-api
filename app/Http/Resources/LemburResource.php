<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
class LemburResource extends JsonResource
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
       public function __construct($status, $message = true, $data = 'List Data Lembur')
    {
        parent::__construct($data);
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
            'id' => $this->id,
            'nama' => $this->nama,
            'npk' => $this->npk,
            'tanggal_spkl' => $this->tanggal_lembur, // Assuming tanggal_spkl maps to tanggal_lembur
            'jam_masuk' => $this->jam_masuk,
            'jam_pulang' => $this->jam_pulang,
        ];
    }
}
