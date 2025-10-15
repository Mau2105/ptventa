<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\SICA\Entities\App;
use Modules\SICA\Entities\Element;
use Modules\SICA\Entities\Category;

class HomeController extends Controller
{
    private function getValidElements($perPage = null, $page = 1)
    {
        $offset = $perPage ? ($page * $perPage) - $perPage : 0;

        // Carga TODOS los elementos del catálogo (sin filtro por stock/inventario)
        $query = Element::with('category')
            ->whereNotNull('image')
            ->whereNotNull('price')
            ->orderBy('created_at', 'desc')  // Recientes primero
            ->get()
            ->filter(function ($element) {
                $path = public_path($element->image);
                if (!file_exists($path)) {
                    return false;
                }
                $extension = strtolower(pathinfo($element->image, PATHINFO_EXTENSION));
                return in_array($extension, ['jpeg', 'jpg', 'png', 'gif', 'webp']);
            });

        $total = $query->count();

        if ($perPage) {
            $slice = $query->slice($offset, $perPage)->values();
            return new LengthAwarePaginator($slice, $total, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        return $query->take(12);  // Para home
    }

    private function getCategories()
    {
        // Categorías con conteo de TODOS los elementos (no solo con stock)
        return Category::withCount('elements')
            ->having('elements_count', '>', 0)
            ->orderBy('name', 'ASC')
            ->get();
    }

    public function welcome()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user_id = $user->person->id;
            if (Session::has('passwords.' . $user_id)) {
                $session_password = Session::get('passwords.' . $user_id);
                if ($user->password === $session_password) {
                    return redirect(route('cefa.password.change.index'));
                }
            }
        }

        $apps = App::all();
        $elements = $this->getValidElements();  // Hasta 12 recientes
        $categories = $this->getCategories();

        return view('welcome', compact('apps', 'elements', 'categories'));
    }

    public function developers()
    {
        return view('designners');
    }

    public function index()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user_id = $user->person->id;
            if (Session::has('passwords.' . $user_id)) {
                $session_password = Session::get('passwords.' . $user_id);
                if ($user->password === $session_password) {
                    return redirect(route('cefa.password.change.index'));
                }
            }
        }

        $apps = App::all();
        $elements = $this->getValidElements();  // Hasta 12
        $categories = $this->getCategories();

        return view('home', compact('apps', 'elements', 'categories'));
    }

    public function ptoventaView()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user_id = $user->person->id;
            if (Session::has('passwords.' . $user_id)) {
                $session_password = Session::get('passwords.' . $user_id);
                if ($user->password === $session_password) {
                    return redirect(route('cefa.password.change.index'));
                }
            }
        }

        $perPage = 12;
        $page = request('page', 1);
        $elements = $this->getValidElements($perPage, $page);  // TODOS paginados
        $categories = $this->getCategories();

        return view('ptventa::ptoventa', compact('elements', 'categories'));
    }
}