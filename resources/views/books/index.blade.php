<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('書籍一覧') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- 検索・絞り込み・ソート --}}
            <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form
                        method="GET"
                        action="{{ route('books.index') }}"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4"
                    >
                        {{-- キーワード --}}
                        <div>
                            <label
                                for="keyword"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                キーワード
                            </label>

                            <input
                                type="text"
                                id="keyword"
                                name="keyword"
                                value="{{ request('keyword') }}"
                                placeholder="タイトル・著者名"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        {{-- ジャンル --}}
                        <div>
                            <label
                                for="genre"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                ジャンル
                            </label>

                            <select
                                id="genre"
                                name="genre"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="">すべて</option>

                                @foreach($genres as $genre)
                                    <option
                                        value="{{ $genre->id }}"
                                        @selected((string) request('genre') === (string) $genre->id)
                                    >
                                        {{ $genre->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ソート --}}
                        <div>
                            <label
                                for="sort"
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                並び順
                            </label>

                            <select
                                id="sort"
                                name="sort"
                                class="w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option
                                    value="latest"
                                    @selected($sort === 'latest')
                                >
                                    新しい順
                                </option>

                                <option
                                    value="oldest"
                                    @selected($sort === 'oldest')
                                >
                                    古い順
                                </option>

                                <option
                                    value="title"
                                    @selected($sort === 'title')
                                >
                                    タイトル順
                                </option>

                                <option
                                    value="rating"
                                    @selected($sort === 'rating')
                                >
                                    評価が高い順
                                </option>
                            </select>
                        </div>

                        {{-- ボタン --}}
                        <div class="flex items-end gap-2">
                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                検索
                            </button>

                            <a
                                href="{{ route('books.index') }}"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                クリア
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 書籍登録ボタン --}}
            <div class="mb-4 flex justify-end">
                <a
                    href="{{ route('books.create') }}"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    書籍を登録
                </a>
            </div>

            {{-- 成功メッセージ --}}
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            {{-- 書籍一覧 --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if($books->isEmpty())
                        <p class="text-gray-500">
                            条件に一致する書籍がありません。
                        </p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                            @foreach($books as $book)
                                <a
                                    href="{{ route('books.show', $book) }}"
                                    class="block border rounded-lg p-4 shadow hover:shadow-lg transition cursor-pointer"
                                >
                                    @if($book->image_url)
                                        <img
                                            src="{{ $book->image_url }}"
                                            alt="{{ $book->title }}"
                                            class="w-full h-48 object-cover mb-4 rounded"
                                        >
                                    @else
                                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center mb-4 rounded">
                                            <span class="text-gray-500">
                                                画像なし
                                            </span>
                                        </div>
                                    @endif

                                    <h3 class="font-bold text-lg mb-2 text-blue-600 hover:text-blue-800">
                                        {{ $book->title }}
                                    </h3>

                                    <p class="text-gray-600 text-sm mb-2">
                                        {{ $book->author }}
                                    </p>

                                    <div class="flex flex-wrap gap-1 mb-2">
                                        @foreach($book->genres as $genre)
                                            <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded">
                                                {{ $genre->name }}
                                            </span>
                                        @endforeach
                                    </div>

                                    @if($book->reviews_avg_rating !== null)
                                        <div class="flex items-center">
                                            <span class="text-yellow-500">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= round($book->reviews_avg_rating))
                                                        ★
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                            </span>

                                            <span class="text-sm text-gray-500 ml-2">
                                                ({{ number_format($book->reviews_avg_rating, 1) }})
                                            </span>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-400">
                                            まだ評価はありません
                                        </p>
                                    @endif
                                </a>
                            @endforeach

                        </div>

                        {{-- ページネーション --}}
                        <div class="mt-6">
                            {{ $books->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>