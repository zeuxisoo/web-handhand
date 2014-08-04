<?php
namespace Hand\Models;

use Illuminate\Database\Eloquent;

class Message extends Eloquent\Model {

    protected $table   = 'message';
    protected $guarded = array('id');

    public function sender() {
        return $this->belongsTo('Hand\Models\User', 'sender_id');
    }

    public function scopeWhereReceiverId($query, $receiver_id) {
        return $query->where('receiver_id', $receiver_id);
    }

    public function scopeWhereHaveRead($query, $status = false) {
        return $query->where('have_read', (int) $status);
    }

    public static function notification($message) {
        $default_info = array_merge([
            'sender_id'   => 0,
            'receiver_id' => 0,
            'category'    => 'system',
            'subject'     => 'You have new message',
            'content'     => '',
            'have_read'   => 0,
        ], $message);

        static::create($default_info);
    }

}
