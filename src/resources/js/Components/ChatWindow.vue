<template>
    <section class="flex h-full w-full flex-col">
        <header class="border-b border-gray-200 px-4 py-3">
            <h3 class="text-base font-semibold text-gray-900" v-text="chatTitle" />
        </header>
        <div class="flex flex-1 flex-col gap-3 overflow-y-auto px-4 py-3">
            <MessageItem
                v-for="message in messages"
                :key="message.id"
                :message="message"
                :is-own="message.user_id === currentUser.id"
                @edit="editMessage"
                @delete="deleteMessage"
                @forward="forwardMessage"
            />
            <p v-if="!messages.length" class="text-sm text-gray-500">Сообщений пока нет.</p>
        </div>
        <form class="flex gap-2 border-t border-gray-200 px-4 py-3" @submit.prevent="sendMessage">
            <input
                v-model="draft"
                type="text"
                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                placeholder="Напишите сообщение..."
            />
            <button
                type="submit"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                :disabled="isSending || !draft.trim()"
            >
                Отправить
            </button>
        </form>
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MessageItem from './MessageItem.vue';
import axios from 'axios';

const props = defineProps({
    chat: {
        type: Object,
        required: true,
    },
    currentUser: {
        type: Object,
        required: true,
    },
});

const messages = ref([]);
const draft = ref('');
const isSending = ref(false);
let channel = null;

const chatTitle = computed(() => {
    if (props.chat.is_group && props.chat.title) {
        return props.chat.title;
    }

    const users = props.chat.users || [];
    return users.map((user) => user.nickname || user.name).join(', ') || 'Диалог';
});

const hydrateMessages = () => {
    messages.value = (props.chat.messages || []).slice().reverse();
};

const subscribe = () => {
    if (!window.Echo || !props.chat?.id) {
        return;
    }

    channel = window.Echo.private(`chat.${props.chat.id}`)
        .listen('MessageSent', (event) => {
            messages.value.push(event.message);
        })
        .listen('MessageUpdated', (event) => {
            const index = messages.value.findIndex((item) => item.id === event.message.id);
            if (index !== -1) {
                messages.value[index] = event.message;
            }
        })
        .listen('MessageDeleted', (event) => {
            messages.value = messages.value.filter((item) => item.id !== event.message_id);
        });
};

const unsubscribe = () => {
    if (channel && window.Echo) {
        window.Echo.leave(`private-chat.${props.chat.id}`);
        channel = null;
    }
};

const sendMessage = async () => {
    if (!draft.value.trim()) {
        return;
    }

    isSending.value = true;
    try {
        const response = await axios.post('/messages', {
            chat_id: props.chat.id,
            body: draft.value.trim(),
        });
        messages.value.push(response.data);
        draft.value = '';
    } finally {
        isSending.value = false;
    }
};

const editMessage = async (payload) => {
    const response = await axios.patch(`/messages/${payload.id}`, {
        body: payload.body,
    });
    const index = messages.value.findIndex((item) => item.id === payload.id);
    if (index !== -1) {
        messages.value[index] = response.data;
    }
};

const deleteMessage = async (messageId) => {
    await axios.delete(`/messages/${messageId}`);
    messages.value = messages.value.filter((item) => item.id !== messageId);
};

const forwardMessage = async (messageId) => {
    const response = await axios.post('/messages/forward', {
        chat_id: props.chat.id,
        message_id: messageId,
    });
    messages.value.push(response.data);
};

watch(
    () => props.chat,
    () => {
        unsubscribe();
        hydrateMessages();
        subscribe();
    },
    { deep: true, immediate: true },
);

onMounted(() => {
    hydrateMessages();
    subscribe();
});

onUnmounted(() => {
    unsubscribe();
});
</script>
