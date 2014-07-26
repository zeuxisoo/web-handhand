<?php
namespace Hand\Controllers;

use Hand\Abstracts\Controller;
use Hand\Models;

class Item extends Controller {

    public function detail($item_id) {
        $item = Models\Item::status('publish')->with('user', 'images')->find($item_id);

        if (empty($item) === true) {
            $this->slim->flash('error', 'Can not found item');
            $this->slim->redirect($this->slim->urlFor('index.index'));
        }else{
            $item_comments   = Models\ItemComment::with('user')->where('item_id', $item_id)->orderBy('created_at', 'asc')->get();
            $item_bookmarked = Models\ItemBookmark::whereUserIdAndItemId($_SESSION['user']['id'], $item_id)->count() >= 1;

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
        $item = Models\Item::find($item_id);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = 'Can not found item';
        }else{
            $bookmark_count = Models\ItemBookmark::whereUserIdAndItemId($_SESSION['user']['id'], $item_id)->count();

            if ($bookmark_count >= 1) {
                $valid_message  = 'The item was bookmarked';
            }else{
                Models\ItemBookmark::create([
                    'user_id' => $_SESSION['user']['id'],
                    'item_id' => $item_id,
                ]);

                $valid_type     = 'success';
                $valid_message  = 'Item bookmarked';
            }
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('item.detail', ['item_id' => $item->id]));
    }

    public function bookmark_delete($item_id) {
        $item = Models\Item::find($item_id);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = 'Can not found item';
        }else{
            $bookmark = Models\ItemBookmark::whereUserIdAndItemId($_SESSION['user']['id'], $item_id);

            if ($bookmark->count() <= 0) {
                $valid_message  = 'The item have not bookmarked';
            }else{
                $bookmark->delete();

                $valid_type     = 'success';
                $valid_message  = 'Item bookmark deleted';
            }
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('item.detail', ['item_id' => $item->id]));
    }

}
