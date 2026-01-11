<template>
    <div class="flex gap-3 rounded-lg border px-3 py-2" :class="isOwn ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-white'">
        <img
            :src="avatarUrl"
            alt="Avatar"
            class="h-9 w-9 rounded-full object-cover"
        />
        <div class="flex flex-1 flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-600" v-text="authorLabel" />
                <span class="text-[10px] text-gray-400" v-text="timeLabel" />
            </div>
        <p class="text-sm text-gray-800" v-text="message.body" />
        <div v-if="message.forwarded_message_id" class="text-xs text-gray-400">
            Пересланное сообщение
        </div>
        <div class="flex flex-wrap gap-2 text-xs text-gray-500">
            <button type="button" class="hover:text-indigo-600" @click="onForward">Переслать</button>
            <button v-if="isOwn" type="button" class="hover:text-indigo-600" @click="toggleEdit">
                Редактировать
            </button>
            <button v-if="isOwn" type="button" class="hover:text-red-500" @click="$emit('delete', message.id)">
                Удалить
            </button>
        </div>
        <div v-if="isEditing" class="flex gap-2">
            <input
                v-model="editedBody"
                type="text"
                class="flex-1 rounded-md border border-gray-300 px-2 py-1 text-xs focus:border-indigo-500 focus:outline-none"
            />
            <button type="button" class="rounded-md bg-indigo-600 px-2 py-1 text-xs text-white" @click="onSave">
                Сохранить
            </button>
        </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    isOwn: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['edit', 'delete', 'forward']);
const isEditing = ref(false);
const editedBody = ref(props.message.body);

watch(
    () => props.message.body,
    (value) => {
        if (!isEditing.value) {
            editedBody.value = value;
        }
    },
);

const authorLabel = computed(() => props.message.sender?.nickname || props.message.sender?.name || 'Пользователь');
const avatarUrl = computed(() => props.message.sender?.avatar_url || '/images/default-avatar.svg');

const timeLabel = computed(() => {
    if (!props.message.created_at) {
        return '';
    }
    return new Date(props.message.created_at).toLocaleTimeString();
});

const toggleEdit = () => {
    isEditing.value = !isEditing.value;
    editedBody.value = props.message.body;
};

const onSave = () => {
    const body = editedBody.value.trim();
    if (!body) {
        return;
    }
    emit('edit', { id: props.message.id, body });
    isEditing.value = false;
};

const onForward = () => {
    emit('forward', props.message.id);
};
</script>
