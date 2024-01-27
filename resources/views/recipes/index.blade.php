<x-app-layout>
  <div class="grid grid-cols-3 gap-4">
    <div class="col-span-2 bg-white rounded p-4">
			{{ Breadcrumbs::render('index') }}
			<div class="mb-4"></div>
      @foreach ($recipes as $recipe)
        <a href=""
          class="flex flex-col items-center bg-white mb-6 border border-gray-200 rounded-lg shadow md:flex-row md:max-w-xl hover:bg-gray-100">
          <img class="object-cover rounded-t-lg h-40 w-40 mrounded-none rounded-l-lg" src="{{ $recipe->image }}"
            alt="{{ $recipe->title }}">
          <div class="flex flex-col justify-between p-4 leading-normal">
            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-800">{{ $recipe->title }}</h5>
            <p class="mb-3 font-normal">{{ $recipe->description }}</p>
            <div class="flex">
              <p class="font-bold mr-2">{{ $recipe->name }}</p>
              <p class="text-gray-500">{{ $recipe->created_at->format('Y年m月d日') }}</p>
            </div>
          </div>
        </a>
        {{-- @include('recipes.partial.horizontal-card') --}}
      @endforeach
			{{ $recipes->links() }}
    </div>

    <div class="col-span-1 bg-white p-4 h-max sticky top-4">
      <form action="{{ route('recipe.index') }}" method="GET">
        <div class="flex">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" class="w-6 h-6 text-gray-700 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
          </svg>
          <h3 class="text-xl text-gray-800 font-bold mb-4">レシピ検索</h3>
        </div>
        <div class="mb-4 p-6 border border-gray-300">
          <label class="text-lg text-gray-800">評価</label>
          <div class="ml-4 mb-2">
						{{-- @if(!is_null($filters['rating']))
							@if($finlters['rating'] == "3")
							<input type="radio" name="rating" value="0" id="rating0" />
						@endif --}}
            <input type="radio" name="rating" value="0" id="rating0" 
						{{ ($filters['rating'] ?? null) == null ? 'checked' : '' }} />
						{{-- もしratingのフィルターがあったら、空文字を返す、そうでなければ'checked'を返す --}}
            <label for="rating0">指定しない</label>
          </div>
          <div class="ml-4 mb-2">
            <input type="radio" name="rating" value="3" id="rating3" 
						{{ ($filters['rating'] ?? null) == "3" ? 'checked' : '' }} />
            <label for="rating3">3以上</label>
          </div>
          <div class="ml-4 mb-2">
            <input type="radio" name="rating" value="4" id="rating4" 
						{{ ($filters['rating'] ?? null) == "4" ? 'checked' : '' }} />
            <label for="rating4">4以上</label>
          </div>
        </div>
        <div class="mb-4 p-6 border border-gray-300">
          <label class="text-lg text-gray-800">カテゴリー</label>
          @foreach ($categories as $category)
            <div class="ml-4 mb-2">
              <input type="checkbox" name="categories[]" value="{{ $category['id'] }}"
                id="category{{ $category['id'] }}" 
								{{ (in_array($category['id'], $filters['categories'] ?? []))? 'checked' : '' }}/>
              <label for="category{{ $category['id'] }}">{{ $category['name'] }}</label>
            </div>
          @endforeach
        </div>
        <input type="text" name="title" value="{{ $filters['title'] ?? '' }}" placeholder="レシピ名を入力"
          class="border border-gray-300 p-2 mb-4 w-full">
				<div class="text-center">
        <button type="submit"
          class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">検索</button>
				</div>
      </form>
    </div>
  </div>

</x-app-layout>
