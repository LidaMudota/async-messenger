<template>
    <Modal :show="show" @close="close">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Добавить контакт</h3>
            <div class="space-y-2">
                <label class="text-xs font-medium text-gray-600">Поиск по nickname или email</label>
                <div class="flex gap-2">
                    <input
                        v-model="query"
                        type="text"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                        placeholder="Введите nickname или email"
                        @keydown.enter.prevent="search"
                    />
                    <button
                        type="button"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-500"
                        :disabled="isLoading"
                        @click="search"
                    >
                        Найти
                    </button>
                </div>
                <p v-if="error" class="text-xs text-red-600" v-text="error" />
            </div>

            <div v-if="isLoading" class="text-sm text-gray-500">Поиск...</div>
            <div v-else class="space-y-2">
                <div
                    v-for="user in results"
                    :key="user.id"
                    class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2"
                >
                    <div class="flex items-center gap-3">
                        <img
                            :src="user.avatar_url"
                            alt="Avatar"
                            class="h-9 w-9 rounded-full object-cover"
                        />
                        <div>
                            <p class="text-sm font-medium text-gray-900" v-text="user.nickname" />
                            <p v-if="user.email" class="text-xs text-gray-500" v-text="user.email" />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-md border border-indigo-600 px-3 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50"
                        :disabled="isAdding(user.id) || isExisting(user.id)"
                        @click="addContact(user.id)"
                    >
                        {{ isExisting(user.id) ? 'В контактах' : 'Добавить' }}
                    </button>
                </div>
                <p v-if="!results.length" class="text-sm text-gray-500">Ничего не найдено.</p>
            </div>

            <div class="flex justify-end">
                <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="close">
                    Закрыть
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import Modal from './Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    existingContactIds: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'added']);

const query = ref('');
const results = ref([]);
const isLoading = ref(false);
const error = ref('');
const addingIds = ref(new Set());

watch(
    () => props.show,
    (value) => {
        if (value) {
            query.value = '';
            results.value = [];
            error.value = '';
            addingIds.value = new Set();
        }
    },
);

const close = () => {
    emit('close');
};

const isExisting = (userId) => props.existingContactIds.includes(userId);

const isAdding = (userId) => addingIds.value.has(userId);

const search = async () => {
    const term = query.value.trim();
    if (term.length < 2) {
        error.value = 'Введите минимум 2 символа для поиска.';
        results.value = [];
        return;
    }

    error.value = '';
    isLoading.value = true;
    try {
        const response = await axios.get('/users/search', {
            params: { query: term },
        });
        results.value = response.data || [];
        if (!results.value.length) {
            error.value = 'Пользователи не найдены.';
        }
    } catch (e) {
        error.value = 'Не удалось выполнить поиск. Попробуйте позже.';
    } finally {
        isLoading.value = false;
    }
};

const addContact = async (userId) => {
    if (isAdding(userId) || isExisting(userId)) {
        return;
    }

    addingIds.value.add(userId);
    try {
        await axios.post('/contacts', { contact_user_id: userId });
        emit('added');
    } catch (e) {
        error.value = 'Не удалось добавить контакт. Проверьте права или попробуйте позже.';
    } finally {
        addingIds.value.delete(userId);
    }
};
</script>
