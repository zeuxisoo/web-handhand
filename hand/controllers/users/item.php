<?php
namespace Hand\Controllers\Users;

use Zeuxisoo\Core\Validator;
use Hand\Abstracts\Controller;
use Hand\Models;
use Hand\Helpers\Upload;
use Hand\Helpers\Image;
use Hand\Helpers\Format;
use Hand\Helpers\Paginate;

class Item extends Controller {

    function __construct() {
        parent::__construct();

        $this->app_config        = $this->slim->config('app.config');
        $this->upload_base_root  = $this->app_config['upload']['item']['root'];
        $this->upload_size_root  = [
            '200x200' => $this->upload_base_root.'/200x200',
            '250x250' => $this->upload_base_root.'/250x250',
            '800x800' => $this->upload_base_root.'/800x800'
        ];
        $this->upload_max_images = 3;
    }

    function create() {
        if ($this->slim->request->isPost() === true) {
            $title       = $this->slim->request->post('title');
            $category    = $this->slim->request->post('category');
            $property    = $this->slim->request->post('property');
            $description = $this->slim->request->post('description');
            $price       = $this->slim->request->post('price');
            $delivery    = $this->slim->request->post('delivery');
            $images      = $_FILES['images'];

            $valdiator = Validator::factory($this->slim->request->post());
            $valdiator->add('title', 'Please enter title')->rule('required')
                      ->add('category', 'Please enter category')->rule('required')
                      ->add('property', 'Please enter property')->rule('required')
                      ->add('description', 'Please enter description')->rule('required')
                      ->add('price', 'Please enter price')->rule('required')
                      ->add('delivery', 'Please enter delivery')->rule('required');

            $valid_type    = 'error';
            $valid_message = '';

            if ($valdiator->inValid() === true) {
                $valid_message = $valdiator->firstError();
            }else if (array_key_exists($category, $this->app_config['item']['category']) === false) {
                $valid_message = 'Category not exists.';
            }else if (array_key_exists($property, $this->app_config['item']['property']) === false) {
                $valid_message = 'Property not exists';
            }else if (array_key_exists($delivery, $this->app_config['item']['delivery']) === false) {
                $valid_message = 'Delivery not exists.';
            }else if (is_numeric($price) === false) {
                $valid_message = 'Invalid price format.';
            }else{
                $uploaded_infos = Upload::instance([
                    'save_root' =>  $this->upload_size_root['800x800'],
                ])->multiUpload($images);

                $uploaded_paths = Format::toUploadedPaths($uploaded_infos);

                Image::instance()->multiResize($uploaded_paths, 800, 800);
                Image::instance(['save_root' => $this->upload_size_root['200x200']])->multiResize($uploaded_paths, 200, 200);
                Image::instance(['save_root' => $this->upload_size_root['250x250']])->multiResize($uploaded_paths, 250, 250);

                $item = Models\Item::create([
                    'title'       => $title,
                    'user_id'     => $_SESSION['user']['id'],
                    'category'    => $category,
                    'property'    => $property,
                    'description' => $description,
                    'price'       => $price,
                    'delivery'    => $delivery,
                ]);

                foreach($uploaded_paths as $uploaded_path) {
                    Models\ItemImage::create([
                        'user_id' => $_SESSION['user']['id'],
                        'item_id' => $item->id,
                        'image'   => basename($uploaded_path)
                    ]);
                }

                $valid_type    = 'success';
                $valid_message = 'The new item was created.';
            }

            $this->slim->flash($valid_type, $valid_message);
            $this->slim->redirect($this->slim->urlFor('user.item.create'));
        }else{
            $this->slim->render('item/create.html', [
                'config' => $this->app_config
            ]);
        }
    }

    function manage() {
        $total    = Models\Item::where('user_id', $_SESSION['user']['id'])->count();
        $paginate = Paginate::instance(['count' => $total, 'size' => 12]);
        $items    = Models\Item::where('user_id', $_SESSION['user']['id'])->take(12)->skip($paginate->offset)->get();
        $items    = Models\Item::fillItemImage($items);

        $this->slim->render('item/manage.html', [
            'items' => $items,
            'paginate' => $paginate->buildPageBar([
                'type' => Paginate::TYPE_BACK_NEXT,
            ])
        ]);
    }

    function delete($item_id) {
        $item = Models\Item::where('user_id', $_SESSION['user']['id'])->find($item_id);

        $valid_type    = 'error';
        $valid_message = '';

        if (empty($item) === true) {
            $valid_message = 'Can not found the item';
        }else{
            $item_title  = $item->title;
            $item_images = Models\ItemImage::where('user_id', $_SESSION['user']['id'])->where('item_id', $item_id)->get();

            foreach($item_images as $item_image) {
                @unlink($this->upload_size_root['200x200'].'/'.$item_image->image);
                @unlink($this->upload_size_root['250x250'].'/'.$item_image->image);
                @unlink($this->upload_size_root['800x800'].'/'.$item_image->image);

                $item_image->delete();
            }

            $item->delete();

            $valid_type    = 'success';
            $valid_message = 'Deleted item named '.$item_title;
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor('user.item.manage'));
    }

    function edit_detail($item_id) {
        $item = Models\Item::where('user_id', $_SESSION['user']['id'])->find($item_id);

        if (empty($item) === true) {
            $this->slim->flash('error', 'Can not found item');
            $this->slim->redirect($this->slim->urlFor('user.item.manage'));
        }else{
            if ($this->slim->request->isPost() === true) {
                $title       = $this->slim->request->post('title');
                $category    = $this->slim->request->post('category');
                $property    = $this->slim->request->post('property');
                $description = $this->slim->request->post('description');
                $price       = $this->slim->request->post('price');
                $delivery    = $this->slim->request->post('delivery');

                $valdiator = Validator::factory($this->slim->request->post());
                $valdiator->add('title', 'Please enter title')->rule('required')
                          ->add('category', 'Please enter category')->rule('required')
                          ->add('property', 'Please enter property')->rule('required')
                          ->add('description', 'Please enter description')->rule('required')
                          ->add('price', 'Please enter price')->rule('required')
                          ->add('delivery', 'Please enter delivery')->rule('required');

                $valid_type    = 'error';
                $valid_message = '';

                if ($valdiator->inValid() === true) {
                    $valid_message = $valdiator->firstError();
                }else if (array_key_exists($category, $this->app_config['item']['category']) === false) {
                    $valid_message = 'Category not exists.';
                }else if (array_key_exists($property, $this->app_config['item']['property']) === false) {
                    $valid_message = 'Property not exists';
                }else if (array_key_exists($delivery, $this->app_config['item']['delivery']) === false) {
                    $valid_message = 'Delivery not exists.';
                }else if (is_numeric($price) === false) {
                    $valid_message = 'Invalid price format.';
                }else{
                    $item->update([
                        'title'       => $title,
                        'category'    => $category,
                        'property'    => $property,
                        'description' => $description,
                        'price'       => $price,
                        'delivery'    => $delivery,
                    ]);

                    $valid_type    = 'success';
                    $valid_message = 'The new item was updated.';
                }

                $this->slim->flash($valid_type, $valid_message);
                $this->slim->redirect($this->slim->urlFor('user.item.edit.detail', ['item_id' => $item_id]));
            }else{
                $this->slim->render('item/edit-detail.html', [
                    'item'   => $item,
                    'config' => $this->app_config,
                ]);
            }
        }
    }

    function edit_image_upload($item_id) {
        $item = Models\Item::where('user_id', $_SESSION['user']['id'])->find($item_id);

        if (empty($item) === true) {
            $this->slim->flash('error', 'Can not found item');
            $this->slim->redirect($this->slim->urlFor('user.item.edit.image.upload', ['item_id' => $item_id]));
        }else{
            if ($this->slim->request->isPost() === true) {
                $item_images = Models\ItemImage::where('user_id', $_SESSION['user']['id'])->where('item_id', $item_id)->get();

                $valid_type    = 'error';
                $valid_message = '';

                if (count($item_images) >= $this->upload_max_images) {
                    $valid_message = 'Only allow upload 3 images in each item';
                }else{
                    $uploaded_infos = Upload::instance([
                        'save_root' =>  $this->upload_size_root['800x800'],
                    ])->multiUpload($_FILES['images']);

                    $uploaded_paths = Format::toUploadedPaths($uploaded_infos);

                    Image::instance()->multiResize($uploaded_paths, 800, 800);
                    Image::instance(['save_root' => $this->upload_size_root['200x200']])->multiResize($uploaded_paths, 200, 200);
                    Image::instance(['save_root' => $this->upload_size_root['250x250']])->multiResize($uploaded_paths, 250, 250);

                    foreach($uploaded_paths as $uploaded_path) {
                        Models\ItemImage::create([
                            'user_id' => $_SESSION['user']['id'],
                            'item_id' => $item->id,
                            'image'   => basename($uploaded_path)
                        ]);
                    }

                    $valid_type    = 'success';
                    $valid_message = 'New image uploaded';
                }

                $this->slim->flash($valid_type, $valid_message);
                $this->slim->redirect($this->slim->urlFor('user.item.edit.image.upload', ['item_id' => $item_id]));
            }else{
                $this->slim->render('item/edit-image-upload.html', [
                    'item' => $item
                ]);
            }
        }
    }

    function edit_image_manage($item_id) {
        $item = Models\Item::where('user_id', $_SESSION['user']['id'])->find($item_id);

        if (empty($item) === true) {
            $this->slim->flash('error', 'Can not found item');
            $this->slim->redirect($this->slim->urlFor('user.item.manage'));
        }else{
            $item_images = Models\ItemImage::where('user_id', $_SESSION['user']['id'])->where('item_id', $item_id)->get();

            $this->slim->render('item/edit-image-manage.html', [
                'item'        => $item,
                'item_images' => $item_images
            ]);
        }
    }

    function edit_image_delete($item_id, $item_image_id) {
        $item_image = Models\ItemImage::where('user_id', $_SESSION['user']['id'])->where('item_id', $item_id)->find($item_image_id);

        $valid_type    = 'error';
        $valid_message = '';
        $valid_return  = 'user.item.manage';

        if (empty($item_image) === true) {
            $valid_message = 'Can not found item image';
        }else{
            @unlink($this->upload_size_root['200x200'].'/'.$item_image->image);
            @unlink($this->upload_size_root['250x250'].'/'.$item_image->image);
            @unlink($this->upload_size_root['800x800'].'/'.$item_image->image);

            $item_image->delete();

            $valid_type    = 'success';
            $valid_message = 'Image deleted';
            $valid_return  = 'user.item.edit.image.manage';
        }

        $this->slim->flash($valid_type, $valid_message);
        $this->slim->redirect($this->slim->urlFor($valid_return, [
            'item_id' => $item_image->item_id,
        ]));
    }

}
