<?php
namespace Hand\Controllers;

use Hand\Abstracts\Controller;
use Hand\Helpers\Paginate;
use Hand\Models;

class Bookmark extends Controller {

    public function index() {
        $model_bookmark = Models\ItemBookmark::whereUserId($_SESSION['user']['id']);

        $total     = $model_bookmark->count('id');
        $paginate  = Paginate::instance(['count' => $total, 'size' => 12]);
        $bookmarks = $model_bookmark->take(12)->skip($paginate->offset)->with([
            'item' => function($query) {
                $query->with('images');
            }
        ])->get(['id', 'item_id']);

        $this->slim->render('bookmark/index.html', [
            'bookmarks' => $bookmarks,
            'paginate'  => $paginate->buildPageBar([
                'type' => Paginate::TYPE_BACK_NEXT,
            ])
        ]);
    }

}
