<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    public function index()
    {
        if ($_SESSION['role'] !== 'admin') {
            $this->redirect('/');
        }
        $users = User::all();
        $this->view('users/index', ['users' => $users]);
    }

    public function profile()
    {
        $user = User::findById($_SESSION['user_id']);
        $this->view('users/profile', ['user' => $user]);
    }
}
