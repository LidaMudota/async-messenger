<template>
    <Modal :show="show" @close="close">
        <div class="space-y-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900">Переслать сообщение</h3>
            <div class="space-y-2">
                <label class="text-xs font-medium text-gray-600">Кому переслать</label>
                <div class="max-h-40 space-y-2 overflow-y-auto">
                    <label
                        v-for="user in contacts"
                        :key="user.id"
                        class="flex items-center gap-2 text-sm text-gray-700"
                    >
                        <input
                            v-model="selectedId"
                            type="radio"
                            :value="user.id"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span v-text="user.nickname || user.name" />
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="close">
                    Отмена
                </button>
                <button
                    type="button"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500"
                    :disabled="!selectedId"
                    @click="forward"
                >
                    Переслать
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import Modal from './Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    contacts: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['forward', 'close']);
const selectedId = ref(null);

watch(
    () => props.show,
    (value) => {
        if (value) {
            selectedId.value = null;
        }
    },
);

const close = () => {
    emit('close');
};

const forward = () => {
    if (!selectedId.value) {
        return;
    }

    emit('forward', { recipient_id: selectedId.value });
};
</script>
