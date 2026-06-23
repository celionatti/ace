<?php

namespace App\Controllers;

use Ace\Controller;
use Ace\Request;
use App\Models\Post;
use App\Models\Comment;
use Ace\Application;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $postModel = new Post();
        
        $page = (int)($request->getBody()['page'] ?? 1);
        if ($page < 1) $page = 1;
        $perPage = 5;
        
        $pagination = $postModel->paginate($perPage, $page, [], ['order_by' => 'created_at DESC']);
        
        return $this->render('blog/index', [
            'posts' => $pagination['data'],
            'page' => $pagination['current_page'],
            'totalPages' => $pagination['last_page']
        ]);
    }

    public function show(Request $request, string $id)
    {
        $post = Post::findOne(['id' => $id]);
        if (!$post) {
            Application::$app->response->setStatusCode(404);
            return $this->render('errors/404');
        }

        $commentModel = new Comment();
        
        if ($request->isPost()) {
            if (Application::isGuest()) {
                Application::$app->session->setFlash('error', 'You must be logged in to comment.');
                return Application::$app->response->redirect("/blog/{$id}");
            }
            
            $commentModel->loadData($request->getBody());
            $commentModel->post_id = $id;
            $commentModel->user_id = Application::$app->user->id;
            
            if ($commentModel->validate() && $commentModel->save()) {
                Application::$app->session->setFlash('success', 'Comment added.');
                return Application::$app->response->redirect("/blog/{$id}");
            }
        }

        return $this->render('blog/show', [
            'post' => $post,
            'commentModel' => $commentModel
        ]);
    }
}

