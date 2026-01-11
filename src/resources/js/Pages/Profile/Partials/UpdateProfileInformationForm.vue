<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    nickname: user.nickname || '',
    email: user.email,
    hide_email: user.hide_email || false,
    avatar: null,
});

const avatarPreview = computed(() => {
    if (form.avatar) {
        return URL.createObjectURL(form.avatar);
    }

    return user.avatar_url || '/images/default-avatar.svg';
});

const onAvatarChange = (event) => {
    const file = event.target.files[0];
    form.avatar = file || null;
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.post(route('profile.update'), { method: 'patch', forceFormData: true })"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="avatar" value="Avatar" />

                <div class="mt-2 flex items-center gap-4">
                    <img
                        :src="avatarPreview"
                        alt="Avatar preview"
                        class="h-16 w-16 rounded-full object-cover"
                    />
                    <input
                        id="avatar"
                        type="file"
                        class="text-sm text-gray-600"
                        accept="image/*"
                        @change="onAvatarChange"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="nickname" value="Nickname" />

                <TextInput
                    id="nickname"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.nickname"
                    required
                    autocomplete="nickname"
                />

                <InputError class="mt-2" :message="form.errors.nickname" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center gap-3">
                <input
                    id="hide_email"
                    v-model="form.hide_email"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <InputLabel for="hide_email" value="Скрывать email в поиске" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
