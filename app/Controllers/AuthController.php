<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        $this->view('auth/login');
    }

    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = User::findByUsername($username);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['trang_thai'] === 'bi_khoa') {
                $this->view('auth/login', ['error' => 'Tài khoản đã bị khóa']);
                return;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['vai_tro'];
            $_SESSION['username'] = $user['username'];

            $this->redirect('/');
        } else {
            $this->view('auth/login', ['error' => 'Sai tài khoản hoặc mật khẩu']);
        }
    }

    public function logout()
    {
        session_destroy();
        $this->redirect('/login');
    }
}
