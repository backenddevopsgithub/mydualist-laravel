<x-onboarding.layout
    step="image"
    :step-index="$stepIndex"
    :total-steps="$totalSteps"
    title="Add a List Image"
    subtitle="Add a meaningful image to make your list more personal and engaging."
>
    <form
        method="POST"
        action="{{ route('onboarding.store', 'image') }}"
        enctype="multipart/form-data"
        x-data="{
            preview: @js($coverImageUrl),
            fileName: '',
            previewFile(event) {
                const file = event.target.files[0]
                if (! file) return

                this.fileName = file.name
                this.preview = URL.createObjectURL(file)
            },
        }"
    >
        @csrf

        <div class="space-y-6">
            <label class="group relative flex min-h-72 cursor-pointer flex-col items-center justify-center overflow-hidden rounded-3xl border-2 border-dashed border-stone-300 bg-stone-50 px-6 py-10 text-center transition hover:border-emerald-400 hover:bg-emerald-50/50">
                <template x-if="preview">
                    <img :src="preview" alt="Selected list cover preview" class="absolute inset-0 h-full w-full object-cover">
                </template>
                <div class="absolute inset-0 bg-gradient-to-t from-emerald-950/60 via-emerald-950/10 to-transparent opacity-0 transition group-hover:opacity-100" x-bind:class="preview ? 'opacity-100' : ''"></div>

                <div class="relative z-10 rounded-2xl bg-white/90 p-6 shadow-sm ring-1 ring-emerald-950/10">
                    <svg class="mx-auto h-11 w-11 text-emerald-800" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 16V7m0 0L8.5 10.5M12 7l3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5 17.5V19h14v-1.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <span class="mt-4 block text-[1.075rem] font-bold text-stone-950" x-text="preview ? 'Replace image' : 'Upload an image'"></span>
                    <span class="mt-2 block text-sm text-stone-600" x-text="fileName || 'Click to choose or drag and drop'"></span>
                    <span class="mt-2 block text-xs font-semibold text-stone-500">JPG, PNG, WEBP up to 2MB</span>
                </div>
                <input type="file" name="cover_image" accept="image/*" class="sr-only" x-on:change="previewFile">
            </label>

            @error('cover_image')
                <p class="text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror

            <p x-show="preview" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                Image selected. Continue to save it with your list.
            </p>

            <div class="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 ring-1 ring-emerald-900/5">
                <span class="font-bold">Pro Tip:</span>
                Images make your list more engaging and can increase the dua requests you receive.
            </div>
        </div>

        <x-onboarding.actions back="dates" />
    </form>
</x-onboarding.layout>
