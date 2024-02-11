<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\RecipeCreateRequest;
use App\Models\Category;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Step;

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

    if (!empty($filters)) {
      //もしカテゴリーが選択されていたら
      if (!empty($fulters['categories'])) {
        //カテゴリーで絞り込みが選択したカテゴリーIDが含まれているレシピを取得
        $query->whereIn('recipes.category_id', $filters['categories']);
      }
      if (!empty($filters['rating'])) {
        //評価で絞り込み
        $query->havingRaw('AVG(reviews.rating) >= ?', [$filters['rating']])
          ->orderBy('rating', 'desc');
      }
      if (!empty($filters['title'])) {
        //タイトルで絞り込み、あいまい検索
        $query->where('recipes.title', 'like', '%' . $filters['title'] . '%');
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
    $categories = Category::all();

    return view('recipes.create', compact('categories'));
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(RecipeCreateRequest $request)
  {
    $posts = $request->all();
    $uuid = Str::uuid()->toString();
    // dd($posts);
    $image = $request->file('image');
    // s3に画像をアップロード
    $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
    // dd($path);
    // s3のURLを取得
    $url = Storage::disk('s3')->url($path);
    // DBにはURLを保存
    try {
      DB::beginTransaction();
      Recipe::insert([
        'id' => $uuid,
        'title' => $posts['title'],
        'description' => $posts['description'],
        'category_id' => $posts['category'],
        'image' => $url,
        'user_id' => Auth::id()
      ]);
      // $posts['ingredients'] =$posts['ingredients'][0]['name']
      // $posts['ingredients'] =$posts['ingredients'][0]['quantity']
      $ingredients = [];
      foreach ($posts['ingredients'] as $key => $ingredient) {
        $ingredients[$key] = [
          'recipe_id' => $uuid,
          'name' => $ingredient['name'],
          'quantity' => $ingredient['quantity']
        ];
      }
      Ingredient::insert($ingredients);
      $steps = [];
      foreach ($posts['steps'] as $key => $step) {
        $steps[$key] = [
          'recipe_id' => $uuid,
          'step_number' => $key + 1,
          'description' => $step
        ];
      }
      STEP::insert($steps);
      DB::commit();
    } catch (\Throwable $th) {
      DB::rollback();
      \Log::debug(print_r($th->getMessage()));
      throw $th;
    }
    // dd($steps);
    flash()->success('レシピを投稿しました。');

    return redirect()->route('recipe.show', ['id' => $uuid]);
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

    //レシピの投稿者とログインユーザーが同じかどうか
    $is_my_recipe = false;
    if (Auth::check() && (Auth::id() === $recipe['user_id'])) {
      $is_my_recipe = true;
    }

    //リレーションで材料とステップを取得
    // dd($recipe);

    return view('recipes.show', compact('recipe', 'is_my_recipe'));
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id)
  {
    $recipe = Recipe::with(['ingredients', 'steps', 'reviews.user', 'user'])
      ->where('recipes.id', $id)
      ->first();
    $categories = Category::all();

    return view('recipes.edit', compact('recipe', 'categories'));
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    $posts = $request->all();
    // dd($posts);
    // 画像の分岐
    $update_array = [
      'title' => $posts['title'],
      'description' => $posts['description'],
      'category_id' => $posts['category_id']
    ];
    if ($request->hasFile('image')) {
      $image = $request->file('image');
      // s3に画像をアップロード
      $path = Storage::disk('s3')->putFile('recipe', $image, 'public');
      // s3のURLを取得
      $url = Storage::disk('s3')->url($path);
      // DBにはURLを保存
      $update_array['image'] = $url;
    }
    try {
      DB::beginTransaction();
      Recipe::where('id', $id)->update($update_array);
      Ingredient::where('recipe_id', $id)->delete();
      STEP::where('recipe_id', $id)->delete();
      $ingredients = [];
      foreach ($posts['ingredients'] as $key => $ingredient) {
        $ingredients[$key] = [
          'recipe_id' => $id,
          'name' => $ingredient['name'],
          'quantity' => $ingredient['quantity']
        ];
      }
      Ingredient::insert($ingredients);
      $steps = [];
      foreach ($posts['steps'] as $key => $step) {
        $steps[$key] = [
          'recipe_id' => $id,
          'step_number' => $key + 1,
          'description' => $step
        ];
      }
      STEP::insert($steps);
      DB::commit();
    } catch (\Throwable $th) {
      DB::rollBack();
      \Log::debug(print_r($th->getMessage(), true));
      throw $th;
    }
    flash()->success('レシピを更新しました！');

    return redirect()->route('recipe.show', ['id' => $id]);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    //
  }
}
