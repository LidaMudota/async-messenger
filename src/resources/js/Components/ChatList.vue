<template>
    <aside class="flex h-full w-full flex-col gap-2 border-r border-gray-200 p-4">
        <h2 class="text-sm font-semibold text-gray-700">Чаты</h2>
        <div class="flex flex-1 flex-col gap-2 overflow-y-auto">
            <button
                v-for="chat in chats"
                :key="chat.id"
                type="button"
                class="flex w-full flex-col gap-1 rounded-lg border px-3 py-2 text-left transition"
                :class="[
                    chat.id === activeChatId ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white',
                    chat.notifications_enabled === false ? 'opacity-60' : '',
                ]"
                @click="$emit('select', chat)"
            >
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-900" v-text="chatLabel(chat)" />
                    <span v-if="chat.notifications_enabled === false" class="text-xs text-gray-500">���</span>
                </div>
                <span class="text-xs text-gray-500" v-text="lastMessagePreview(chat)" />
            </button>
        </div>
    </aside>
</template>

<script setup>
defineProps({
    chats: {
        type: Array,
        default: () => [],
    },
    activeChatId: {
        type: [Number, String],
        default: null,
    },
});

defineEmits(['select']);

const chatLabel = (chat) => {
    if (chat.is_group && chat.title) {
        return chat.title;
    }

    if (chat.users?.length) {
        return chat.users.map((user) => user.nickname || user.name).join(', ');
    }

    return 'Диалог';
};

const lastMessagePreview = (chat) => {
    const lastMessage = chat.messages?.[0];
    if (!lastMessage) {
        return 'Сообщений пока нет';
    }

    const text = lastMessage.body || '';
    return text.length > 50 ? `${text.slice(0, 50)}…` : text;
};
</script>
