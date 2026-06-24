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
            hasFile: false,
            previewFile(event) {
                const file = event.target.files[0];
                if (! file) return;
                this.fileName = file.name;
                this.preview = URL.createObjectURL(file);
                this.hasFile = true;
            },
            removeImage() {
                this.preview = null;
                this.fileName = '';
                this.hasFile = false;
                this.$refs.fileInput.value = '';
            },
        }"
    >
        @csrf
        <input type="hidden" name="remove_image" value="0" x-ref="removeFlag">

        <div class="space-y-6">
            <div class="space-y-2 text-sm leading-6 text-stone-700">
                <p>Upload an image that we'll include in confirmation emails when you complete Duas.</p>
                <p>We can also remind you closer to your start date.</p>
                <p>This is optional.</p>
            </div>

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
                <input type="file" name="cover_image" accept="image/*" class="sr-only" x-ref="fileInput" x-on:change="previewFile">
            </label>

            <button
                type="button"
                x-show="preview"
                x-on:click="removeImage(); $refs.removeFlag.value = '1'"
                class="w-full rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-bold text-red-700 transition hover:bg-red-100"
            >
                Remove image
            </button>

            @error('cover_image')
                <p class="text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-10 flex items-center justify-between gap-4">
            <a href="{{ route('onboarding.show', 'dates') }}" class="inline-flex items-center gap-2 text-sm font-bold text-emerald-900 hover:text-emerald-700">
                <span aria-hidden="true">←</span>
                Back
            </a>
            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-800 px-6 py-3 text-sm font-bold text-white shadow-sm shadow-emerald-950/10 transition hover:bg-emerald-700"
                x-text="hasFile || preview ? 'Next' : 'Remind me later'"
            >
                Remind me later
            </button>
        </div>
    </form>
</x-onboarding.layout>
