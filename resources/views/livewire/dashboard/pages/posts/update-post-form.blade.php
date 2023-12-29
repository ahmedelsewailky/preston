<?php

use App\Models\Post;
use Livewire\Volt\Component;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $title;

    #[Rule('required')]
    public $content;

    #[Rule('required|exists:categories,id')]
    public $category_id;

    #[Rule('required|exists:tags,id')]
    public $tags = [];

    #[Rule('required|in:published,draft')]
    public $status;

    public $image;

    #[Rule('nullable|simetimes|image|mimes:jpg,jpeg,png|max:2048')]
    public $imageInputField;

    public $post;

    public function mount(Post $post)
    {
        $this->post = $post;
        $this->fill($post->only('title', 'content', 'category_id', 'status', 'image'));
        $this->tags = $post->tags->pluck('id');
    }

    public function update()
    {
        $this->validate();

        Storage::delete('public/' . $this->image);

        $this->post->update([
            'title' => $this->post->title,
            'slug' => $this->post->slug,
            'content' => $this->content,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'image' => $this->imageInputField ? $this->imageInputField->store('posts', 'public') : $this->image,
        ]);

        $this->post->tags()->sync($this->tags);

        return $this->redirect(route('posts.index'));
    }
};

?>

<div>
    <x-slot name="breadcrumbs">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" wire:navigate>{{ __('Home') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('posts.index') }}" wire:navigate>{{ __('Posts') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Create New Post') }}</li>
    </x-slot>

    <div class="container">
        <form wire:submit.prevent="update">
            <div class="row">
                <div class="col-12 col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <!-- Title -->
                            <div class="mb-3">
                                <x-input-label for="title" value="{{ __('Title') }}" />
                                <input type="text" id="title" wire:model="title" disabled class="form-control" />
                                <p class="mt-1 text-muted small">{{ __('Can not be changed the post title') }}</p>
                            </div>
                            <!-- Content -->
                            <div class="mb-3" wire:ignore>
                                <x-input-label for="content" value="{{ __('Content') }}" />
                                <textarea wire:model="content" id="content" class="form-control {{ $errors->get('content') ? 'is-invalid' : false }}"
                                    cols="30" rows="10">
                                </textarea>
                            </div>
                            @error('content')
                                <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <!-- Category -->
                            <div class="mb-3">
                                <x-input-label for="category" value="{{ __('Category') }}" />
                                <select wire:model="category_id" id="category_id"
                                    class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="" hidden>{{ __('--Select--') }}</option>
                                    @foreach (\App\Models\Category::get() as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <p class="invalid-feedback">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tags -->
                            <div wire:ignore>
                                <x-input-label for="tag" value="{{ __('Tags') }}" />
                                <select wire:model="tags" id="tag"
                                    class="form-select @error('tags') is-invalid @enderror" multiple>
                                    <option value="" hidden>{{ __('--Select--') }}</option>
                                    @foreach (\App\Models\Tag::get() as $tag)
                                        <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                    @endforeach
                                </select>
                                @error('tags')
                                    <p class="invalid-feedback">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Image -->
                            <div class="mb-3">
                                <x-input-label for="image" value="{{ __('Featured Image') }}" />
                                <x-text-input type="file" id="image" wire:model="imageInputField"
                                    :class="$errors->get('imageInputField') ? 'is-invalid' : false" />
                                @error('imageInputField')
                                    <p class="invalid-feedback">{{ $message }}</p>
                                @enderror

                                <div class="mt-3 text-center">
                                    @if ($imageInputField)
                                        <img src="{{ $imageInputField->temporaryUrl() }}" width="225" height="150"
                                            class="mw-100" alt="">
                                    @else
                                        <img src="{{ asset('storage\\') . $post->image }}" width="225"
                                            height="150" class="mw-100" alt="">
                                    @endif
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <x-input-label for="status" value="{{ __('Status') }}" />
                                <select wire:model="status" id="status"
                                    class="form-select @error('status') is-invalid @enderror">
                                    <option value="" hidden>{{ __('--Select--') }}</option>
                                    <option value="published">{{ __('Published') }}</option>
                                    <option value="draft">{{ __('Draft') }}</option>
                                </select>
                                @error('status')
                                    <p class="invalid-feedback">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Submit -->
                            <div class="pt-3 border-top">
                                <button type="submit" class="btn btn-dark">{{ __('Publish') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@push('css')
    <link rel="stylesheet" href="{{ asset('dashboard/vendors/summernote/summernote-lite.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard/vendors/select2/css/select2.css') }}">
@endpush

@push('js')
    <script src="{{ asset('dashboard/vendors/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('dashboard/vendors/summernote/summernote-lite.min.js') }}"></script>
    <script>
        $(function() {
            $('#content').summernote({
                height: 300,
                minHeight: null,
                maxHeight: null,
                focus: true,
                callbacks: {
                    onBlur: function(contents, $editable) {
                        @this.set('content', contents.target.innerHTML);
                    }
                },
            });

            $("#tag").select2({
                containerCss : {width: 'resolve'}
            });

            $("#tag").on("change", function(e) {
                var data = $(this).select2("val");
                console.log(data);
                @this.set("tags", data);
            });
        });
    </script>
@endpush
