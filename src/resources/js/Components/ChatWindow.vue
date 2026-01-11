<template>
    <section class="flex h-full w-full flex-col">
        <header class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
            <h3 class="text-base font-semibold text-gray-900" v-text="chatTitle" />
            <button
                type="button"
                class="text-xs font-medium text-gray-600 hover:text-indigo-600"
                @click="toggleNotifications"
            >
                {{ notificationsEnabled ? 'Отключить уведомления' : 'Включить уведомления' }}
            </button>
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

        <ForwardMessageModal
            :show="isForwardModalOpen"
            :contacts="contacts"
            @close="closeForwardModal"
            @forward="confirmForward"
        />
    </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MessageItem from './MessageItem.vue';
import ForwardMessageModal from './ForwardMessageModal.vue';
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
    contacts: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['notifications-updated', 'refresh-chats']);
const messages = ref([]);
const draft = ref('');
const isSending = ref(false);
const isForwardModalOpen = ref(false);
const forwardMessageId = ref(null);
let channel = null;

const notificationsEnabled = computed(() => props.chat.notifications_enabled !== false);

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
            if (event.message.user_id !== props.currentUser.id) {
                playNotificationSound();
            }
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

const toggleNotifications = async () => {
    const response = await axios.patch(`/chats/${props.chat.id}/notifications`, {
        enabled: !notificationsEnabled.value,
    });

    emit('notifications-updated', response.data.notifications_enabled);
};

const playNotificationSound = () => {
    if (!notificationsEnabled.value) {
        return;
    }

    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) {
        return;
    }

    const context = new AudioContext();
    const oscillator = context.createOscillator();
    const gain = context.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.value = 880;
    gain.gain.value = 0.05;

    oscillator.connect(gain);
    gain.connect(context.destination);

    oscillator.start();
    oscillator.stop(context.currentTime + 0.12);
    oscillator.onended = () => context.close();
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
    forwardMessageId.value = messageId;
    isForwardModalOpen.value = true;
};

const closeForwardModal = () => {
    isForwardModalOpen.value = false;
    forwardMessageId.value = null;
};

const confirmForward = async (payload) => {
    if (!forwardMessageId.value) {
        return;
    }

    await axios.post('/messages/forward', {
        message_id: forwardMessageId.value,
        recipient_id: payload.recipient_id,
    });

    closeForwardModal();
    emit('refresh-chats');
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
