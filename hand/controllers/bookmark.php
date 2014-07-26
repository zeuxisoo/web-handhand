<?php
namespace Hand\Controllers;

use Hand\Abstracts\Controller;
use Hand\Helpers\Paginate;
use Hand\Models;

class Bookmark extends Controller {

    public function index() {
        $total     = Models\ItemBookmark::where('user_id', $_SESSION['user']['id'])->count();
        $paginate  = Paginate::instance(['count' => $total, 'size' => 12]);
        $bookmarks = Models\ItemBookmark::where('user_id', $_SESSION['user']['id'])->take(12)->skip($paginate->offset)->with([
            'item' => function($query) {
                $query->with('images');
            }
        ])->get();

        $this->slim->render('bookmark/index.html', [
            'bookmarks' => $bookmarks,
            'paginate'  => $paginate->buildPageBar([
                'type' => Paginate::TYPE_BACK_NEXT,
            ])
        ]);
    }

}
