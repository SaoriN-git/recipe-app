<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
  public function home()
  {
    //get all recipes
    $recipes = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name')
      ->join('users', 'users.id', '=', 'recipes.user_id')
      ->orderBy('created_at', 'desc')
      ->limit(3)
      ->get();
      // dd($recipes);

      $popular = Recipe::select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'recipes.views', 'users.name')
      ->join('users', 'users.id', '=', 'recipes.user_id')
      ->orderBy('recipes.views', 'desc')
      ->limit(2)
      ->get();
      // dd($popular);

    return view('home', compact('recipes', 'popular'));
  }
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    $filters = $request->all();
    // dd($filters);

    $query = Recipe::query()->select('recipes.id', 'recipes.title', 'recipes.description', 'recipes.created_at', 'recipes.image', 'users.name', \DB::raw('AVG(reviews.rating) as rating'))
      // ->where('recipes.deleted_at', null)
      ->join('users', 'users.id', '=', 'recipes.user_id')
      ->leftJoin('reviews', 'reviews.recipe_id', '=', 'recipes.id')
      ->groupBy('recipes.id')
      ->orderBy('created_at', 'desc');

      if( !empty($filters) ) {
          //もしカテゴリーが選択されていたら
        if( !empty($fulters['categories']) ) {
          //カテゴリーで絞り込みが選択したカテゴリーIDが含まれているレシピを取得
          $query->whereIn('recipes.category_id', $filters['categories']);
        }
        if( !empty($filters['rating']) ) {
          //評価で絞り込み
          $query->havingRaw('AVG(reviews.rating) >= ?', [$filters['rating']])
          ->orderBy('rating', 'desc');
        }
        if( !empty($filters['title']) ) {
          //タイトルで絞り込み、あいまい検索
          $query->where('recipes.title', 'like', '%'.$filters['title'].'%');
        }
      }
      $recipes = $query->paginate(5);
      // dd($recipes);

    $categories = Category::all();

      return view('recipes.index', compact('recipes', 'categories', 'filters'));
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    //
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    $recipe = Recipe::with(['ingredients', 'steps', 'reviews.user', 'user'])
      ->where('recipes.id', $id)
      ->first();
      $recipe_recorde = Recipe::find($id);
      $recipe_recorde->increment('views');
    //リレーションで材料とステップを取得
    // dd($recipe);

    return view('recipes.show', compact('recipe'));
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    //
  }
}
