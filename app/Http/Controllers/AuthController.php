<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest; // ← ここを追加
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    /**
     * 会員登録処理
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        // 1. バリデーション済みのデータを取得し、ユーザーを作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 2. 登録と同時にログインさせる（セッション認証）
        Auth::login($user);

        // 3. ログイン後のトップページなどにリダイレクト
        return redirect()->route('login'); // または適宜ダッシュボードやホーム画面へ
    }

    /**
     * ログイン処理
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        // 1. フォームのリクエストからメールアドレスとパスワードを取得
        $credentials = $request->only('email', 'password');

        // 2. 認証を試みる
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // 3. ログイン成功時のリダイレクト（適宜ダッシュボードやホーム画面へ）
            return redirect()->intended('/');
        }

        // 4. 認証失敗時のエラーメッセージの返却（仕様書指定のメッセージ）
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }
}
