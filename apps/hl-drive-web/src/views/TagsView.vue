<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useTags } from '@/composables/useTags';
import { usePermissions } from '@/composables/usePermissions';

const { tags, loading, error, fetchTags, createTag, deleteTag } = useTags();
const { canManageTags } = usePermissions();

const showCreateModal = ref(false);
const lockToEditTags = ref(false);
const newTagName = ref('');

onMounted(() => {
    fetchTags();
});

async function handleCreate() {
    if (!newTagName.value.trim()) {
        return;
    }

    try {
        await createTag(newTagName.value);

        showCreateModal.value = false;
        newTagName.value = '';
    } catch {
        // Error handled in composable
    }
}

async function handleDelete(id: number) {
    if (confirm('Are you sure you want to delete this tag?')) {
        await deleteTag(id);
    }
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <UIPageHeader title="Tags" description="Manage tags to categorize your time entries.">
            <template v-slot:actions>
                <CButton
                    v-if="canManageTags"
                    @click="lockToEditTags = !lockToEditTags"
                    class="inline-flex items-center gap-2"
                    :icon="
                        lockToEditTags
                            ? 'material-symbols:lock-open-outline-rounded'
                            : 'material-symbols:lock-open-right-outline-rounded'
                    "
                    :preset="lockToEditTags ? 'outlined-black' : 'outlined-danger'"
                >
                    {{ lockToEditTags ? 'Lock to edit' : 'Unlock to edit' }}
                </CButton>

                <CButton v-if="canManageTags" @click="showCreateModal = true">New Tag</CButton>
            </template>
        </UIPageHeader>

        <div v-if="error" class="mb-4 rounded-lg bg-red-100 p-4 text-red-700">
            {{ error }}
        </div>

        <div v-if="loading" class="py-8 text-center">Loading...</div>

        <div v-else class="flex flex-wrap gap-2">
            <div
                v-for="tag in tags"
                :key="tag.id"
                class="flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow tag-pill"
            >
                <span class="text-gray-900">{{ tag.name }}</span>
                <CButton
                    preset="outlined-red-sm"
                    v-if="canManageTags && lockToEditTags"
                    class="text-red-500 hover:text-red-700"
                    @click="handleDelete(tag.id)"
                >
                    ×
                </CButton>
            </div>
        </div>

        <div v-if="!loading && !tags.length" class="py-8 text-center text-gray-500">
            No tags yet. Create one to start categorizing entries.
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">New Tag</h2>
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Name</label>
                    <input
                        v-model="newTagName"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                        @keyup.enter="handleCreate"
                    />
                </div>
                <div class="flex justify-end gap-2">
                    <button
                        class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200"
                        @click="showCreateModal = false"
                    >
                        Cancel
                    </button>
                    <CButton @click="handleCreate">Create</CButton>
                </div>
            </div>
        </div>
    </div>
</template>
