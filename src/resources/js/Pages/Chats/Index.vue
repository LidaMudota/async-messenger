<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ChatList from '@/Components/ChatList.vue';
import ChatWindow from '@/Components/ChatWindow.vue';
import GroupCreateModal from '@/Components/GroupCreateModal.vue';

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const chats = ref([]);
const activeChat = ref(null);
const isLoading = ref(false);
const isGroupModalOpen = ref(false);
const contacts = ref([]);

const loadChats = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/chats');
        chats.value = response.data;
        if (!activeChat.value && chats.value.length) {
            await selectChat(chats.value[0]);
        }
    } finally {
        isLoading.value = false;
    }
};

const loadContacts = async () => {
    const response = await axios.get('/contacts');
    contacts.value = response.data
        .map((item) => item.contact)
        .filter(Boolean);
};

const selectChat = async (chat) => {
    if (!chat?.id) {
        return;
    }

    const response = await axios.get(`/chats/${chat.id}`);
    activeChat.value = response.data;
};

const openGroupModal = () => {
    isGroupModalOpen.value = true;
};

const closeGroupModal = () => {
    isGroupModalOpen.value = false;
};

const createGroup = async (payload) => {
    const response = await axios.post('/chats', {
        is_group: true,
        title: payload.title || 'Группа',
        user_ids: payload.user_ids,
    });

    chats.value = [response.data, ...chats.value];
    activeChat.value = response.data;
    closeGroupModal();
};

onMounted(() => {
    loadChats();
    loadContacts();
});
</script>

<template>
    <Head title="Chats" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Chats
                </h2>
                <button
                    type="button"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                    @click="openGroupModal"
                >
                    Новая группа
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex h-[70vh] overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="w-full max-w-xs">
                        <ChatList
                            :chats="chats"
                            :active-chat-id="activeChat?.id"
                            @select="selectChat"
                        />
                    </div>
                    <div class="flex flex-1 flex-col">
                        <div v-if="isLoading" class="flex flex-1 items-center justify-center text-sm text-gray-500">
                            Загрузка чатов...
                        </div>
                        <ChatWindow
                            v-else-if="activeChat"
                            :chat="activeChat"
                            :current-user="currentUser"
                        />
                        <div v-else class="flex flex-1 items-center justify-center text-sm text-gray-500">
                            Выберите чат, чтобы начать переписку.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <GroupCreateModal
            :show="isGroupModalOpen"
            :users="contacts"
            @close="closeGroupModal"
            @create="createGroup"
        />
    </AuthenticatedLayout>
</template>
