<template>
    <Modal :show="show" @close="close">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Новая группа</h3>
            <div class="space-y-2">
                <label class="text-xs font-medium text-gray-600">Название группы</label>
                <input
                    v-model="title"
                    type="text"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                />
            </div>
            <div class="space-y-2">
                <label class="text-xs font-medium text-gray-600">Участники</label>
                <input
                    v-model="search"
                    type="text"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                    placeholder="Поиск по контактам"
                />
                <div class="max-h-40 space-y-2 overflow-y-auto">
                    <label
                        v-for="user in filteredUsers"
                        :key="user.id"
                        class="flex items-center gap-2 text-sm text-gray-700"
                    >
                        <input
                            v-model="selectedIds"
                            type="checkbox"
                            :value="user.id"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span v-text="user.nickname || user.name" />
                    </label>
                    <p v-if="!filteredUsers.length" class="text-xs text-gray-500">Контакты не найдены.</p>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="close">
                    Отмена
                </button>
                <button
                    type="button"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500"
                    :disabled="!selectedIds.length"
                    @click="createGroup"
                >
                    Создать
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Modal from './Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['create', 'close']);

const title = ref('');
const selectedIds = ref([]);
const search = ref('');

const filteredUsers = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) {
        return props.users;
    }

    return props.users.filter((user) => {
        const nickname = (user.nickname || '').toLowerCase();
        const name = (user.name || '').toLowerCase();
        const email = (user.email || '').toLowerCase();
        return nickname.includes(term) || name.includes(term) || email.includes(term);
    });
});

watch(
    () => props.show,
    (value) => {
        if (value) {
            title.value = '';
            selectedIds.value = [];
            search.value = '';
        }
    },
);

const close = () => {
    emit('close');
};

const createGroup = () => {
    emit('create', {
        title: title.value.trim(),
        user_ids: selectedIds.value,
    });
};
</script>
