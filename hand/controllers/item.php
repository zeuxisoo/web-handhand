<?php
namespace Hand\Controllers;

use Hand\Abstracts\Controller;
use Hand\Models;

class Item extends Controller {

    public function detail($item_id) {
        $item = Models\Item::with('user', 'images')->find($item_id);

        if (empty($item) === true) {
            $this->slim->flash('error', 'Can not found item');
            $this->slim->redirect($this->slim->urlFor('index.index'));
        }else if (in_array($item->status, ['hide', 'block']) === true) {
            $this->slim->flash('error', 'The item is protected');
            $this->slim->redirect($this->slim->urlFor('index.index'));
        }else{
            $item_comments   = Models\ItemComment::with('user')->where('item_id', $item_id)->orderBy('created_at', 'asc')->get();

            if (isset($_SESSION['user']['id']) === true) {
                $item_bookmarked = Models\ItemBookmark::whereUserIdAndItemId($_SESSION['user']['id'], $item_id)->count('id') >= 1;
            }else{
                $item_bookmarked = false;
            }

            $this->slim->render('item/detail.html', [
                'item'            => $item,
                'item_comments'   => $item_comments,
                'item_bookmarked' => $item_bookmarked
            ]);
        }
    }

    public function detail_comment($item_id) {
        $item        = Models\Item::status('publish')->find($item_id);
        $content     = $this->slim->request->post('content');
        $redirect_to = $this->slim->urlFor('item.detail', ['item_id' => $item_id]);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = 'Can not found item';
        }elseif (empty($content) === true) {
            $valid_message = 'Please enter comment content';
        }else{
            $item_comment = Models\ItemComment::create([
                'user_id' => $_SESSION['user']['id'],
                'item_id' => $item->id,
                'content' => $content,
            ]);

            $valid_type     = 'success';
            $valid_message  = 'New comment created';
            $redirect_to   .= "#item-detail-comment-id-".$item_comment->id;
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($redirect_to);
    }

    public function bookmark_create($item_id) {
        $item = Models\Item::status('publish')->where('user_id', $_SESSION['user']['id'])->find($item_id);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = "Can not found item";
        }else if ($item->bookmarks !== null && $item->bookmarks->count('id') >= 1) {
            $valid_message = "The item was bookmarked";
        }else{
            Models\ItemBookmark::create([
                'user_id' => $_SESSION['user']['id'],
                'item_id' => $item_id,
            ]);

            $valid_type     = 'success';
            $valid_message  = 'Item bookmarked';
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('item.detail', ['item_id' => $item_id]));
    }

    public function bookmark_delete($item_id) {
        $item = Models\Item::where('user_id', $_SESSION['user']['id'])->find($item_id);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = "Can not found item";
        }else if ($item->bookmarks !== null && $item->bookmarks->count('id') <= 0) {
            $valid_message = "The item have not bookmarked";
        }else{
            $item->bookmarks()->delete();

            $valid_type     = 'success';
            $valid_message  = 'Item bookmark deleted';
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('item.detail', ['item_id' => $item_id]));
    }

    public function trade($item_id) {
        $item = Models\Item::status('publish')->find($item_id);

        $valid_type    = 'error';
        $valid_message = '';
        $redirect_to   = $this->slim->urlFor('item.detail', ['item_id' => $item_id]);

        if (empty($item) === true) {
            $valid_message = "Can not found item";
        }else if ($item->user_id == $_SESSION['user']['id']) {
            $valid_message = "The item owner can not make trade action";
        }else{
            $item->update([
                'status' => 'trade'
            ]);

            Models\ItemTrade::create([
                'user_id' => $_SESSION['user']['id'],
                'item_id' => $item->id
            ]);

            // Notify item owner, the trade is success
            if ($item->user->settings->notify_trade == 1) {
                Models\Message::notification([
                    'receiver_id' => $item->user_id,
                    'subject'     => "Item [".$item->title."] was changed to trade status.",
                    'content'     => join("\n", [
                        "Please click the [item menu] > [manage item] > [trade] to get more information.",
                        "===============================================================",
                        "- Item name: ".$item->title,
                        "- Trade user: ".$_SESSION['user']['username']
                    ])
                ]);
            }

            $valid_type     = 'success';
            $valid_message  = 'The item was added to your trade list';
            $redirect_to    = $this->slim->urlFor('index.index');
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($redirect_to);
    }

}
