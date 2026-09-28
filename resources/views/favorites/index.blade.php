<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('お気に入り一覧') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($books->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($books as $book)
                                <div class="border rounded-lg p-4 hover:shadow-lg transition-shadow">
                                    @if($book->image_url)
                                        <img src="{{ $book->image_url }}" alt="{{ $book->title }}"
                                            class="w-full h-48 object-cover rounded mb-4">
                                    @else
                                        <div class="w-full h-48 bg-gray-200 rounded mb-4 flex items-center justify-center">
                                            <span class="text-gray-400">No Image</span>
                                        </div>
                                    @endif

                                    <h3 class="font-bold text-lg mb-2">
                                        <a href="{{ route('books.show', $book) }}" class="text-blue-600 hover:underline">
                                            {{ $book->title }}
                                        </a>
                                    </h3>

                                    <p class="text-gray-600 mb-2">
                                        {{ $book->author }}
                                    </p>

                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">
                                            ISBN: {{ $book->isbn ?? '未登録' }}
                                        </span>

                                        <form action="{{ route('favorites.toggle', $book) }}" method="POST"
                                            class="favorite-form">
                                            @csrf

                                            <button type="submit" class="favorite-button text-red-500 hover:text-red-700"
                                                aria-label="お気に入りを解除">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path
                                                        d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $books->links() }}
                        </div>
                    @else
                        <p class="text-gray-500">
                            お気に入りに登録された書籍はありません。
                        </p>

                        <a href="{{ route('books.index') }}" class="mt-4 inline-block text-blue-600 hover:underline">
                            書籍一覧を見る
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.favorite-form').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const button = form.querySelector('.favorite-button');
                const svg = button.querySelector('svg');

                // 現在、赤いハートかどうか
                const isFavorite = svg.getAttribute('fill') === 'currentColor';

                // 連打防止
                button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': form.querySelector(
                                'input[name="_token"]'
                            ).value,
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('お気に入りの更新に失敗しました。');
                    }

                    if (isFavorite) {
                        // ❤️ → ♡
                        svg.setAttribute('fill', 'none');
                        svg.setAttribute('stroke', 'currentColor');
                        svg.setAttribute('stroke-width', '2');

                        button.classList.remove(
                            'text-red-500',
                            'hover:text-red-700'
                        );

                        button.classList.add(
                            'text-gray-400',
                            'hover:text-red-500'
                        );

                        button.setAttribute('aria-label', 'お気に入りに追加');
                    } else {
                        // ♡ → ❤️
                        svg.setAttribute('fill', 'currentColor');
                        svg.removeAttribute('stroke');
                        svg.removeAttribute('stroke-width');

                        button.classList.remove(
                            'text-gray-400',
                            'hover:text-red-500'
                        );

                        button.classList.add(
                            'text-red-500',
                            'hover:text-red-700'
                        );

                        button.setAttribute('aria-label', 'お気に入りを解除');
                    }
                } catch (error) {
                    alert('お気に入りの更新に失敗しました。');
                } finally {
                    button.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>